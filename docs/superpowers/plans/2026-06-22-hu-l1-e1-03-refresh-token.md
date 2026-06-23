# HU-L1-E1-03 · Refresh de token — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar `POST /api/v1/token/refresh` que rota el refresh token (en cookie HttpOnly) y re-emite el access token, y mover el refresh del login E1-02 a esa misma cookie.

**Architecture:** Hexagonal + CQRS ligero. Caso de uso `RefreshTokenHandler` (lado Query) que consume el refresh (lo revoca), carga el `User` por id y re-emite access + refresh. El transporte de la cookie vive en una fábrica de Infraestructura reutilizada por Login y Refresh. Decisión registrada en ADR 0006.

**Tech Stack:** PHP 8.4 · Symfony 7.4 · Doctrine DBAL · Redis (phpredis) · Lexik JWT (RS256) · PHPUnit · PHPStan 9.

## Global Constraints

- Dominio puro: `Domain/` no importa Symfony/Doctrine; framework e infra solo en `Infrastructure/`.
- Escritura→Command, Lectura→Query; los handlers implementan `App\Shared\Application\Bus\QueryHandler` (autoconfigurados al `query.bus` por `_instanceof` en `services.yaml`).
- Errores de dominio → RFC 7807 vía `ProblemJsonSubscriber`; un 401 se logra extendiendo `App\Shared\Domain\UnauthorizedException`.
- Cookie de refresh: `HttpOnly` + `SameSite=Strict` + `Secure` condicional (`app.refresh_cookie_secure`: `false` en dev, `true` en prod), path `/api/v1`, TTL 604800 s (7 días, igual que el store).
- Respuesta uniforme ante fallo (no revela la causa).
- PHPStan nivel 9 y CS-Fixer deben quedar verdes (`make lint-api`). Cobertura ≥ 80 % en Domain/Application.
- IDs: UUID v7. El claim de identidad del access token es el `userId`.

---

## File Structure

**Crear**
- `apps/api/src/Identity/Domain/Exception/InvalidRefreshToken.php` — excepción 401 del refresh.
- `apps/api/src/Identity/Application/Query/RefreshTokenQuery.php` — DTO de entrada (`refreshToken`).
- `apps/api/src/Identity/Application/Query/RefreshTokenResult.php` — DTO de salida (`accessToken`, `refreshToken`, `expiresIn`).
- `apps/api/src/Identity/Application/Query/RefreshTokenHandler.php` — caso de uso.
- `apps/api/src/Identity/Infrastructure/Http/RefreshTokenCookie.php` — fábrica/lectura de la cookie.
- `apps/api/src/Identity/Infrastructure/Http/RefreshTokenController.php` — endpoint.
- `apps/api/tests/Unit/Identity/Application/RefreshTokenHandlerTest.php`
- `apps/api/tests/Unit/Identity/Infrastructure/Http/RefreshTokenCookieTest.php`
- `apps/api/tests/Integration/Identity/RefreshEndpointTest.php`

**Modificar**
- `apps/api/src/Identity/Application/Port/RefreshTokenStore.php` — `+ consume()`.
- `apps/api/src/Identity/Application/Port/UserRepository.php` — `+ ofById()`.
- `apps/api/src/Identity/Infrastructure/Security/RedisRefreshTokenStore.php` — implementar `consume()`.
- `apps/api/src/Identity/Infrastructure/Persistence/Doctrine/DoctrineUserRepository.php` — implementar `ofById()`.
- `apps/api/src/Identity/Infrastructure/Http/LoginController.php` — refresh a cookie; body sin `refresh_token`.
- `apps/api/config/routes/identity.yaml` — ruta `api_token_refresh`.
- `apps/api/config/services.yaml` — registrar controller, parámetro `app.refresh_cookie_secure`, binding de `RefreshTokenCookie`.
- `apps/api/tests/Support/Identity/FakeRefreshTokenStore.php` — fake con estado (`consume`).
- `apps/api/tests/Support/Identity/InMemoryUserRepository.php` — `+ ofById()`.
- `apps/api/tests/Integration/Identity/LoginEndpointTest.php` — body sin `refresh_token`; assert cookie.
- `docs/security.md`, `docs/api/api-design.md`, `docs/product/legends/L1-identidad.md` — contrato actualizado.

> Comandos de test: `make test-api` (Unit+Functional, sin stack) · `make test-integration` (requiere `make up`). PHPStan/estilo: `make lint-api`.

---

## Task 1: Fábrica de cookie de refresh

**Files:**
- Create: `apps/api/src/Identity/Infrastructure/Http/RefreshTokenCookie.php`
- Create: `apps/api/tests/Unit/Identity/Infrastructure/Http/RefreshTokenCookieTest.php`
- Modify: `apps/api/config/services.yaml`

**Interfaces:**
- Produces: `RefreshTokenCookie::create(string $token): Symfony\Component\HttpFoundation\Cookie` y `RefreshTokenCookie::read(Symfony\Component\HttpFoundation\Request $request): ?string`.

- [ ] **Step 1: Write the failing test**

`apps/api/tests/Unit/Identity/Infrastructure/Http/RefreshTokenCookieTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Http;

use App\Identity\Infrastructure\Http\RefreshTokenCookie;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class RefreshTokenCookieTest extends TestCase
{
    public function testCreaCookieHttpOnlySameSiteStrict(): void
    {
        $cookie = (new RefreshTokenCookie(secure: false))->create('tok-123');

        self::assertSame('refresh_token', $cookie->getName());
        self::assertSame('tok-123', $cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
        self::assertFalse($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
        self::assertSame('/api/v1', $cookie->getPath());
    }

    public function testLeeLaCookieDelRequest(): void
    {
        $factory = new RefreshTokenCookie(secure: true);
        $request = new Request();
        $request->cookies->set('refresh_token', 'leido');

        self::assertSame('leido', $factory->read($request));
        self::assertNull($factory->read(new Request()));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T api vendor/bin/phpunit tests/Unit/Identity/Infrastructure/Http/RefreshTokenCookieTest.php`
Expected: FAIL (`Class "App\Identity\Infrastructure\Http\RefreshTokenCookie" not found`).

- [ ] **Step 3: Write minimal implementation**

`apps/api/src/Identity/Infrastructure/Http/RefreshTokenCookie.php`:
```php
<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fábrica y lectura de la cookie del refresh token (ADR 0006): HttpOnly + SameSite=Strict,
 * Secure condicional por entorno. La comparten Login, Refresh y (futuro) Logout.
 */
final readonly class RefreshTokenCookie
{
    private const string NAME = 'refresh_token';
    private const string PATH = '/api/v1';
    private const int TTL_SECONDS = 604800;

    public function __construct(private bool $secure)
    {
    }

    public function create(string $token): Cookie
    {
        return Cookie::create(self::NAME, $token)
            ->withHttpOnly(true)
            ->withSecure($this->secure)
            ->withSameSite(Cookie::SAMESITE_STRICT)
            ->withPath(self::PATH)
            ->withExpires(time() + self::TTL_SECONDS);
    }

    public function read(Request $request): ?string
    {
        $value = $request->cookies->get(self::NAME);

        return \is_string($value) && '' !== $value ? $value : null;
    }
}
```

- [ ] **Step 4: Wire the service**

En `apps/api/config/services.yaml`, en `parameters:` (línea 3) añade el valor por defecto y la sobrescritura por entorno; y registra el binding del servicio junto a los demás de Identity (tras la línea 66, `$ttl: 900`):
```yaml
parameters:
    app.refresh_cookie_secure: false
```
```yaml
    App\Identity\Infrastructure\Http\RefreshTokenCookie:
        arguments:
            $secure: '%app.refresh_cookie_secure%'
```
Al final del archivo añade el bloque por entorno:
```yaml
when@prod:
    parameters:
        app.refresh_cookie_secure: true
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -T api vendor/bin/phpunit tests/Unit/Identity/Infrastructure/Http/RefreshTokenCookieTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add apps/api/src/Identity/Infrastructure/Http/RefreshTokenCookie.php apps/api/tests/Unit/Identity/Infrastructure/Http/RefreshTokenCookieTest.php apps/api/config/services.yaml
git commit -m "feat(api): fabrica de cookie HttpOnly para el refresh token (HU-L1-E1-03)"
```

---

## Task 2: Caso de uso RefreshTokenHandler (puertos + dobles)

**Files:**
- Create: `apps/api/src/Identity/Domain/Exception/InvalidRefreshToken.php`
- Create: `apps/api/src/Identity/Application/Query/RefreshTokenQuery.php`
- Create: `apps/api/src/Identity/Application/Query/RefreshTokenResult.php`
- Create: `apps/api/src/Identity/Application/Query/RefreshTokenHandler.php`
- Create: `apps/api/tests/Unit/Identity/Application/RefreshTokenHandlerTest.php`
- Modify: `apps/api/src/Identity/Application/Port/RefreshTokenStore.php`
- Modify: `apps/api/src/Identity/Application/Port/UserRepository.php`
- Modify: `apps/api/src/Identity/Infrastructure/Security/RedisRefreshTokenStore.php`
- Modify: `apps/api/src/Identity/Infrastructure/Persistence/Doctrine/DoctrineUserRepository.php`
- Modify: `apps/api/tests/Support/Identity/FakeRefreshTokenStore.php`
- Modify: `apps/api/tests/Support/Identity/InMemoryUserRepository.php`

**Interfaces:**
- Consumes: `RefreshTokenStore::issueFor(UserId): string`, `AccessTokenIssuer::issueFor(User): AccessToken` (`->token`, `->expiresIn`), `User::id(): UserId`.
- Produces:
  - `RefreshTokenStore::consume(string $token): ?UserId` (valida y revoca; `null` si no existe).
  - `UserRepository::ofById(UserId $id): ?User`.
  - `RefreshTokenQuery(string $refreshToken)` con `public string $refreshToken`.
  - `RefreshTokenResult(string $accessToken, string $refreshToken, int $expiresIn)` con esas propiedades públicas.
  - `RefreshTokenHandler::__invoke(RefreshTokenQuery): RefreshTokenResult`.
  - `InvalidRefreshToken::create(): self` (extiende `UnauthorizedException`).

- [ ] **Step 1: Update test doubles to support the new port methods**

`apps/api/tests/Support/Identity/FakeRefreshTokenStore.php` (con estado para soportar rotación):
```php
<?php

declare(strict_types=1);

namespace App\Tests\Support\Identity;

use App\Identity\Application\Port\RefreshTokenStore;
use App\Identity\Domain\UserId;

final class FakeRefreshTokenStore implements RefreshTokenStore
{
    /** @var array<string, string> token => userId */
    private array $tokens = [];

    public function issueFor(UserId $userId): string
    {
        $token = 'refresh-'.$userId->value().'-'.\count($this->tokens);
        $this->tokens[$token] = $userId->value();

        return $token;
    }

    public function consume(string $token): ?UserId
    {
        $userId = $this->tokens[$token] ?? null;
        if (null === $userId) {
            return null;
        }
        unset($this->tokens[$token]); // rotación: el token usado se revoca

        return UserId::fromString($userId);
    }
}
```

`apps/api/tests/Support/Identity/InMemoryUserRepository.php` (añadir índice por id y `ofById`):
```php
<?php

declare(strict_types=1);

namespace App\Tests\Support\Identity;

use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Email;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $byEmail = [];
    /** @var array<string, User> */
    private array $byId = [];

    public function save(User $user): void
    {
        $this->byEmail[$user->email()->value()] = $user;
        $this->byId[$user->id()->value()] = $user;
    }

    public function ofEmail(Email $email): ?User
    {
        return $this->byEmail[$email->value()] ?? null;
    }

    public function ofById(UserId $id): ?User
    {
        return $this->byId[$id->value()] ?? null;
    }
}
```

- [ ] **Step 2: Write the failing test**

`apps/api/tests/Unit/Identity/Application/RefreshTokenHandlerTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\Query\RefreshTokenHandler;
use App\Identity\Application\Query\RefreshTokenQuery;
use App\Identity\Domain\Email;
use App\Identity\Domain\Exception\InvalidRefreshToken;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Tests\Support\Identity\FakeAccessTokenIssuer;
use App\Tests\Support\Identity\FakePasswordHasher;
use App\Tests\Support\Identity\FakeRefreshTokenStore;
use App\Tests\Support\Identity\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class RefreshTokenHandlerTest extends TestCase
{
    private function handler(InMemoryUserRepository $repo, FakeRefreshTokenStore $store): RefreshTokenHandler
    {
        return new RefreshTokenHandler($store, $repo, new FakeAccessTokenIssuer());
    }

    private function repoWithUser(UserId $id): InMemoryUserRepository
    {
        $repo = new InMemoryUserRepository();
        $repo->save(User::register($id, new Email('user@tickets.local'), (new FakePasswordHasher())->hash('x'), null));

        return $repo;
    }

    public function testRefreshValidoRotaYReemiteTokens(): void
    {
        $id = UserId::generate();
        $store = new FakeRefreshTokenStore();
        $oldRefresh = $store->issueFor($id);

        $result = $this->handler($this->repoWithUser($id), $store)(new RefreshTokenQuery($oldRefresh));

        self::assertNotSame('', $result->accessToken);
        self::assertNotSame('', $result->refreshToken);
        self::assertNotSame($oldRefresh, $result->refreshToken); // rotó
        self::assertGreaterThan(0, $result->expiresIn);
    }

    public function testElRefreshPrevioQuedaRevocadoTrasUsarlo(): void
    {
        $id = UserId::generate();
        $store = new FakeRefreshTokenStore();
        $oldRefresh = $store->issueFor($id);
        $handler = $this->handler($this->repoWithUser($id), $store);

        $handler(new RefreshTokenQuery($oldRefresh));

        $this->expectException(InvalidRefreshToken::class); // el token previo ya no sirve
        $handler(new RefreshTokenQuery($oldRefresh));
    }

    public function testRefreshDesconocidoEsRechazado(): void
    {
        $this->expectException(InvalidRefreshToken::class);

        $this->handler(new InMemoryUserRepository(), new FakeRefreshTokenStore())(new RefreshTokenQuery('inexistente'));
    }

    public function testUsuarioInexistenteEsRechazado(): void
    {
        $id = UserId::generate();
        $store = new FakeRefreshTokenStore();
        $refresh = $store->issueFor($id); // emitido, pero el repo no tiene ese usuario

        $this->expectException(InvalidRefreshToken::class);

        $this->handler(new InMemoryUserRepository(), $store)(new RefreshTokenQuery($refresh));
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `docker compose exec -T api vendor/bin/phpunit tests/Unit/Identity/Application/RefreshTokenHandlerTest.php`
Expected: FAIL (clases `RefreshTokenHandler`/`RefreshTokenQuery`/`InvalidRefreshToken` no existen).

- [ ] **Step 4: Extend the ports**

`apps/api/src/Identity/Application/Port/RefreshTokenStore.php` — añade dentro de la interfaz:
```php
    /**
     * Valida el refresh token y lo revoca (rotación). Devuelve el UserId asociado, o null si no existe.
     */
    public function consume(string $token): ?UserId;
```

`apps/api/src/Identity/Application/Port/UserRepository.php` — añade el import `use App\Identity\Domain\UserId;` y dentro de la interfaz:
```php
    public function ofById(UserId $id): ?User;
```

- [ ] **Step 5: Implement the adapters**

`RedisRefreshTokenStore` — añade el método (usa `GETDEL` atómico: leer + borrar en una operación):
```php
    public function consume(string $token): ?UserId
    {
        if ('' === $token) {
            return null;
        }
        /** @var string|false $value */
        $value = $this->redis->getDel(self::PREFIX.$token);
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        return UserId::fromString($value);
    }
```

`DoctrineUserRepository` — añade el método (mismo patrón que `ofEmail`):
```php
    public function ofById(UserId $id): ?User
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, email, password, name, roles FROM users WHERE id = :id',
            ['id' => $id->value()],
        );

        if (false === $row) {
            return null;
        }

        return User::reconstitute(
            UserId::fromString($this->str($row, 'id')),
            new Email($this->str($row, 'email')),
            new HashedPassword($this->str($row, 'password')),
            $this->nullableStr($row, 'name'),
            $this->roles($this->str($row, 'roles')),
        );
    }
```

- [ ] **Step 6: Create the exception and DTOs**

`apps/api/src/Identity/Domain/Exception/InvalidRefreshToken.php`:
```php
<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\UnauthorizedException;

final class InvalidRefreshToken extends UnauthorizedException
{
    public static function create(): self
    {
        // Mensaje uniforme: no distingue token inválido, expirado o usuario inexistente.
        return new self('Refresh token inválido o ausente.');
    }
}
```

`apps/api/src/Identity/Application/Query/RefreshTokenQuery.php`:
```php
<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

final readonly class RefreshTokenQuery
{
    public function __construct(public string $refreshToken)
    {
    }
}
```

`apps/api/src/Identity/Application/Query/RefreshTokenResult.php`:
```php
<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

final readonly class RefreshTokenResult
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {
    }
}
```

- [ ] **Step 7: Create the handler**

`apps/api/src/Identity/Application/Query/RefreshTokenHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

use App\Identity\Application\Port\AccessTokenIssuer;
use App\Identity\Application\Port\RefreshTokenStore;
use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Exception\InvalidRefreshToken;
use App\Shared\Application\Bus\QueryHandler;

/**
 * Caso de uso «Refresh» (HU-L1-E1-03): consume el refresh (lo revoca), carga el usuario
 * y re-emite access + refresh. Respuesta uniforme ante fallo (no revela la causa).
 */
final readonly class RefreshTokenHandler implements QueryHandler
{
    public function __construct(
        private RefreshTokenStore $refreshTokens,
        private UserRepository $users,
        private AccessTokenIssuer $accessTokens,
    ) {
    }

    public function __invoke(RefreshTokenQuery $query): RefreshTokenResult
    {
        $userId = $this->refreshTokens->consume($query->refreshToken) ?? throw InvalidRefreshToken::create();
        $user = $this->users->ofById($userId) ?? throw InvalidRefreshToken::create();

        $access = $this->accessTokens->issueFor($user);
        $refresh = $this->refreshTokens->issueFor($userId);

        return new RefreshTokenResult($access->token, $refresh, $access->expiresIn);
    }
}
```

- [ ] **Step 8: Run unit tests + static analysis**

Run: `docker compose exec -T api vendor/bin/phpunit --testsuite Unit`
Expected: PASS (incluye los 4 nuevos tests y los de login sin romper).
Run: `docker compose exec -T api vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
Expected: sin errores (puertos e implementadores coherentes).

- [ ] **Step 9: Commit**

```bash
git add apps/api/src/Identity apps/api/tests/Support/Identity apps/api/tests/Unit/Identity/Application/RefreshTokenHandlerTest.php
git commit -m "feat(api): caso de uso refresh de token con rotacion (HU-L1-E1-03)"
```

---

## Task 3: Endpoint POST /api/v1/token/refresh

**Files:**
- Create: `apps/api/src/Identity/Infrastructure/Http/RefreshTokenController.php`
- Create: `apps/api/tests/Integration/Identity/RefreshEndpointTest.php`
- Modify: `apps/api/config/routes/identity.yaml`
- Modify: `apps/api/config/services.yaml`

**Interfaces:**
- Consumes: `QueryBus::ask(RefreshTokenQuery): RefreshTokenResult`, `RefreshTokenCookie::read()/create()`.
- Produces: ruta `api_token_refresh` → `POST /api/v1/token/refresh`.

- [ ] **Step 1: Write the failing integration test**

`apps/api/tests/Integration/Identity/RefreshEndpointTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integración de POST /api/v1/token/refresh (HU-L1-E1-03): rota el refresh (cookie) y re-emite access.
 * Requiere el stack arriba (postgres + redis + claves JWT).
 */
final class RefreshEndpointTest extends WebTestCase
{
    public function testRefreshConCookieValidaRotaYDevuelveAccess(): void
    {
        $client = self::createClient();
        $this->truncateUsers();
        $this->register($client, 'refresh@tickets.local', 'Secreta123');
        $this->login($client, 'refresh@tickets.local', 'Secreta123');

        $client->request('POST', '/api/v1/token/refresh');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{access_token?: string, refresh_token?: string, expires_in?: int} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertNotEmpty($data['access_token'] ?? '');
        self::assertArrayNotHasKey('refresh_token', $data); // el refresh no viaja en el body
        self::assertNotNull($client->getResponse()->headers->getCookies()[0] ?? null); // sí en cookie
    }

    public function testSinCookieDevuelve401(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/v1/token/refresh');

        self::assertResponseStatusCodeSame(401);
    }

    public function testCookieInvalidaDevuelve401(): void
    {
        $client = self::createClient();
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('refresh_token', 'no-existe'));

        $client->request('POST', '/api/v1/token/refresh');

        self::assertResponseStatusCodeSame(401);
    }

    private function login(KernelBrowser $client, string $email, string $password): void
    {
        $client->request(
            'POST',
            '/api/v1/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
    }

    private function register(KernelBrowser $client, string $email, string $password): void
    {
        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password], \JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(201);
    }

    private function truncateUsers(): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        \assert($connection instanceof Connection);
        $connection->executeStatement('TRUNCATE users');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T api vendor/bin/phpunit tests/Integration/Identity/RefreshEndpointTest.php`
Expected: FAIL (404 en `/api/v1/token/refresh`: ruta/controller no existen).

- [ ] **Step 3: Create the controller**

`apps/api/src/Identity/Infrastructure/Http/RefreshTokenController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Query\RefreshTokenQuery;
use App\Identity\Application\Query\RefreshTokenResult;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /api/v1/token/refresh (HU-L1-E1-03). Lee el refresh de la cookie, rota y re-emite el access.
 * El nuevo refresh vuelve en la cookie; nunca en el body. 401 si la cookie falta o es inválida.
 */
final readonly class RefreshTokenController
{
    public function __construct(
        private QueryBus $queryBus,
        private RefreshTokenCookie $cookie,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->ask(new RefreshTokenQuery($this->cookie->read($request) ?? ''));
        \assert($result instanceof RefreshTokenResult);

        $response = new JsonResponse(
            ['access_token' => $result->accessToken, 'expires_in' => $result->expiresIn],
            Response::HTTP_OK,
        );
        $response->headers->setCookie($this->cookie->create($result->refreshToken));

        return $response;
    }
}
```

- [ ] **Step 4: Register the route and the controller service**

`apps/api/config/routes/identity.yaml` — añade al final:
```yaml
api_token_refresh:
    path: /api/v1/token/refresh
    controller: App\Identity\Infrastructure\Http\RefreshTokenController
    methods: [POST]
```

`apps/api/config/services.yaml` — junto a los otros controllers (tras el bloque de `LoginController`, línea 34):
```yaml
    App\Identity\Infrastructure\Http\RefreshTokenController:
        tags: ['controller.service_arguments']
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -T api vendor/bin/phpunit tests/Integration/Identity/RefreshEndpointTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Commit**

```bash
git add apps/api/src/Identity/Infrastructure/Http/RefreshTokenController.php apps/api/tests/Integration/Identity/RefreshEndpointTest.php apps/api/config/routes/identity.yaml apps/api/config/services.yaml
git commit -m "feat(api): endpoint POST /token/refresh (HU-L1-E1-03)"
```

---

## Task 4: Login setea la cookie (cambia el contrato E1-02)

**Files:**
- Modify: `apps/api/src/Identity/Infrastructure/Http/LoginController.php`
- Modify: `apps/api/tests/Integration/Identity/LoginEndpointTest.php`

**Interfaces:**
- Consumes: `RefreshTokenCookie::create()`, `LoginResult` (`->accessToken`, `->refreshToken`, `->expiresIn`).
- Produces: body de `/api/v1/login` = `{ access_token, expires_in }` + `Set-Cookie: refresh_token`.

- [ ] **Step 1: Update the login integration test (refleja el nuevo contrato)**

En `apps/api/tests/Integration/Identity/LoginEndpointTest.php`, reemplaza el cuerpo de `testLoginExitosoDevuelveTokens` (líneas 32-39) por:
```php
        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        /** @var array{access_token?: string, refresh_token?: string, expires_in?: int} $data */
        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertNotEmpty($data['access_token'] ?? '');
        self::assertGreaterThan(0, $data['expires_in'] ?? 0);
        self::assertArrayNotHasKey('refresh_token', $data); // ahora va en cookie, no en el body
        $cookie = $client->getResponse()->headers->getCookies()[0] ?? null;
        self::assertNotNull($cookie);
        self::assertSame('refresh_token', $cookie->getName());
        self::assertTrue($cookie->isHttpOnly());
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T api vendor/bin/phpunit tests/Integration/Identity/LoginEndpointTest.php`
Expected: FAIL (`testLoginExitosoDevuelveTokens`: aún hay `refresh_token` en el body y no hay cookie).

- [ ] **Step 3: Modify the LoginController**

Reemplaza `apps/api/src/Identity/Infrastructure/Http/LoginController.php` por:
```php
<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Query\LoginQuery;
use App\Identity\Application\Query\LoginResult;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * POST /api/v1/login (HU-L1-E1-02). Devuelve el access token en el body y el refresh en
 * cookie HttpOnly (ADR 0006). 401 ante credenciales inválidas.
 */
final readonly class LoginController
{
    public function __construct(
        private QueryBus $queryBus,
        private RefreshTokenCookie $cookie,
    ) {
    }

    public function __invoke(#[MapRequestPayload] LoginRequest $request): JsonResponse
    {
        $result = $this->queryBus->ask(new LoginQuery($request->email, $request->password));
        \assert($result instanceof LoginResult);

        $response = new JsonResponse(
            ['access_token' => $result->accessToken, 'expires_in' => $result->expiresIn],
            Response::HTTP_OK,
        );
        $response->headers->setCookie($this->cookie->create($result->refreshToken));

        return $response;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec -T api vendor/bin/phpunit tests/Integration/Identity/LoginEndpointTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add apps/api/src/Identity/Infrastructure/Http/LoginController.php apps/api/tests/Integration/Identity/LoginEndpointTest.php
git commit -m "feat(api): login emite el refresh en cookie HttpOnly (HU-L1-E1-03, ADR 0006)"
```

---

## Task 5: Actualizar la documentación del contrato

**Files:**
- Modify: `docs/security.md`
- Modify: `docs/api/api-design.md`
- Modify: `docs/product/legends/L1-identidad.md`

**Interfaces:** documentación; sin test automatizado (verificación por lectura).

- [ ] **Step 1: `docs/security.md`**
  - §2: cambiar la línea del refresh para reflejar HttpOnly + Secure + SameSite=Strict **sin** token CSRF, citando ADR 0006.
  - §5 (fila CSRF): reemplazar "`SameSite=Strict` + token CSRF en el refresh" por "`SameSite=Strict` en la cookie de refresh (token CSRF: 2ª iteración, ADR 0006)".
  - §7: marcar como decidido (ADR 0006) el punto "Decidir si el refresh va en cookie httpOnly o en cuerpo".

- [ ] **Step 2: `docs/api/api-design.md`**
  - Junto a la fila `POST /api/v1/login`, anotar: respuesta `200 { access_token, expires_in }` + `Set-Cookie: refresh_token` (HttpOnly).
  - Junto a `POST /api/v1/token/refresh`: lee la cookie `refresh_token`; respuesta `200 { access_token, expires_in }` + nueva cookie; `401` si falta o es inválida.

- [ ] **Step 3: `docs/product/legends/L1-identidad.md`**
  - HU-L1-E1-02 «Contrato API»: cambiar a `200 { access_token, expires_in }` + cookie `refresh_token` (HttpOnly, SameSite=Strict).
  - HU-L1-E1-03 «Contrato API»: request sin body (cookie `refresh_token`) → `200 { access_token, expires_in }` + cookie rotada · `401`.

- [ ] **Step 4: Commit**

```bash
git add docs/security.md docs/api/api-design.md docs/product/legends/L1-identidad.md
git commit -m "docs: contrato de login/refresh con cookie HttpOnly (HU-L1-E1-03, ADR 0006)"
```

---

## Cierre

- [ ] **Suite completa + estilo**

Run: `make test-api` (Unit+Functional) y `docker compose exec -T api vendor/bin/phpunit --testsuite Integration` (stack arriba)
Run: `make lint-api` (PHPStan 9 + CS-Fixer + Rector + Deptrac + composer audit)
Expected: todo verde.

- [ ] **PR**

Abrir PR `feat/hu-l1-e1-03-refresh-token` → `main`, esperar CI verde, revisores `revisor-arquitectura` y `revisor-seguridad`.

---

## Self-Review (cobertura del spec/ADR)

- ✅ Refresh en cookie HttpOnly + SameSite=Strict + Secure condicional → Task 1 (fábrica) + parámetro `app.refresh_cookie_secure`.
- ✅ Sin token CSRF → no se implementa; documentado en ADR y Task 5.
- ✅ Rotación (revoca el previo) → `consume` (GETDEL) Task 2 + test `testElRefreshPrevioQuedaRevocado`.
- ✅ Re-emisión del access cargando el User por id → `UserRepository::ofById` Task 2.
- ✅ 401 uniforme (cookie ausente/inválida/usuario inexistente) → `InvalidRefreshToken` + tests Task 2/3.
- ✅ Login cambia a cookie, body sin refresh → Task 4.
- ✅ Solo backend (sin cableado SPA) → fuera de alcance, anotado.
- ✅ Docs actualizadas → Task 5.
- Consistencia de tipos: `consume(string): ?UserId`, `ofById(UserId): ?User`, `RefreshTokenResult(accessToken, refreshToken, expiresIn)` usados igual en handler, controller y tests.
