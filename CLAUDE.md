# laravel-attachments — полиморфни file/image attachments за Laravel

Laravel package (PHP 8.3, illuminate 11–13, PHPUnit, Larastan). Комуникация с потребителя: български. Код, commit-и и PR-и: английски.

Работният флоу (issue-та, PR-и) идва от плъгина `gws@claude-flow` — `/gws:issue <N>`. Този файл носи само спецификите на проекта.

## Branch-ове
- Базов branch: `main`. Issue branch-ове: `fix|feat|chore/N-kratko-ime` от него, PR към него, squash merge.
- Issue-то се затваря с `Fixes #N` в тялото на commit-а (базовият branch е default — затваря се при merge на PR-а).

## Deploy
- Няма — проектът не се качва на сървър. `/gws:ship` не е приложим тук; доставката е merge в базовия branch.

## Build и commit-и
- Няма build стъпка (чист PHP package, без npm).
- Тестове: `composer test` (PHPUnit + Testbench); статичен анализ: `composer analyse` (Larastan).
- Pre-commit hook от `givanov95/laravel-git-hooks` (quality gate); при нужда се прескача със `SKIP_HOOK=1`.
- Commit стил: Conventional Commits на английски (`fix(scope): ...`).

## GitHub
- Нови issue-та се добавят в project board „gws".
