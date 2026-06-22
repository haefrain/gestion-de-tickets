// Conventional Commits (feat, fix, docs, chore, ...). Usado por el hook commit-msg y por CI.
export default {
  extends: ['@commitlint/config-conventional'],
  rules: {
    // Los cuerpos de commit del proyecto incluyen listas y rutas largas.
    'body-max-line-length': [0, 'always'],
    'footer-max-line-length': [0, 'always'],
  },
};
