<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Application\Port\UserRepository;
use App\Identity\Domain\Email;
use App\Identity\Domain\Role;
use App\Identity\Domain\User;
use App\Identity\Domain\UserId;
use App\Search\Application\Port\SearchIndex;
use App\Search\Application\Port\TicketReadModel;
use App\Shared\Application\Clock\Clock;
use App\Ticketing\Application\Port\TicketRepository;
use App\Ticketing\Domain\Category;
use App\Ticketing\Domain\Priority;
use App\Ticketing\Domain\Ticket;
use App\Ticketing\Domain\TicketId;
use App\Ticketing\Domain\TicketStatus;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Datos de demo (HU-L7-E1-02). Resetea a un estado conocido: usuarios de cada rol y tickets de
 * ejemplo, reindexados en Elasticsearch. Idempotente (recargable): trunca y recrea.
 *
 * Credenciales demo (todas con contraseña "Demo1234"):
 *   cliente@demo.local · agente@demo.local · admin@demo.local
 */
#[AsCommand(name: 'app:seed', description: 'Carga datos de demostración (usuarios por rol + tickets) y reindexa.')]
final class SeedDemoCommand extends Command
{
    private const string PASSWORD = 'Demo1234';

    public function __construct(
        private readonly Connection $connection,
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly TicketRepository $tickets,
        private readonly TicketReadModel $readModel,
        private readonly SearchIndex $index,
        private readonly Clock $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->connection->executeStatement('TRUNCATE tickets');
        $this->connection->executeStatement('TRUNCATE users');

        $cliente = $this->createUser('cliente@demo.local', 'Cliente Demo', Role::client());
        $agente = $this->createUser('agente@demo.local', 'Agente Demo', Role::agent());
        $this->createUser('admin@demo.local', 'Admin Demo', Role::admin());

        $clienteId = $cliente->id()->value();
        $agenteId = $agente->id()->value();
        $now = $this->clock->now();

        $this->createTicket($clienteId, 'No puedo iniciar sesión', 'El login devuelve 500 al entrar.', Priority::fromString(Priority::HIGH), Category::fromString('technical'), $now);
        $enCurso = $this->createTicket($clienteId, 'Error al exportar la factura', 'El PDF sale en blanco.', Priority::medium(), Category::fromString('billing'), $now);
        $this->createTicket($clienteId, 'Consulta sobre mi plan', 'Quiero saber qué incluye mi plan.', Priority::fromString(Priority::LOW), Category::general(), $now);

        // Un ticket en curso y asignado, para una demo más rica.
        $enCurso->changeStatus(TicketStatus::fromString(TicketStatus::IN_PROGRESS), $agenteId, $now);
        $enCurso->assignTo($agenteId, $now);
        $this->tickets->save($enCurso);

        // Reindexado completo a un estado conocido.
        $this->index->reset();
        $indexed = 0;
        foreach ($this->readModel->iterateAll() as $document) {
            $this->index->index($document);
            ++$indexed;
        }

        $io->success(\sprintf('Seed cargado: 3 usuarios, 3 tickets, %d indexados. Contraseña demo: "%s".', $indexed, self::PASSWORD));

        return Command::SUCCESS;
    }

    private function createUser(string $email, string $name, Role $role): User
    {
        $user = User::reconstitute(UserId::generate(), new Email($email), $this->hasher->hash(self::PASSWORD), $name, [$role]);
        $this->users->save($user);

        return $user;
    }

    private function createTicket(string $requesterId, string $title, string $description, Priority $priority, Category $category, \DateTimeImmutable $now): Ticket
    {
        $ticket = Ticket::create(TicketId::generate(), $requesterId, $title, $description, $priority, $category, $now);
        $this->tickets->save($ticket);

        return $ticket;
    }
}
