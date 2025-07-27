# Projetaí - Plataforma de Gestão para Feira de Ciências

## Sobre o Projeto

Este projeto é uma plataforma web completa para gerenciar todas as etapas de uma feira de ciências, desde a submissão de projetos até a avaliação e acompanhamento via Kanban.

Estrutura do projeto:
- apiFeira: Backend em Laravel.
- feira_ciencias: Frontend em Vue.js com Vuetify.

## Funcionalidades

- Autenticação de usuários (alunos e avaliadores)
- Submissão e avaliação de projetos
- Feedback detalhado pelos avaliadores
- Kanban por projeto com tarefas organizadas
- Visualização de histórico e informações completas do projeto

## Tecnologias Utilizadas

### Backend
<p>
<img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white">
<img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white">
<img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white">
</p>

### Frontend
<p>
<img src="https://img.shields.io/badge/Vue.js-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white">
<img src="https://img.shields.io/badge/Vuetify-1867C0?style=for-the-badge&logo=vuetify&logoColor=white">
<img src="https://img.shields.io/badge/Pinia-FFD859?style=for-the-badge&logo=pinia&logoColor=black">
<img src="https://img.shields.io/badge/Axios-5A29E4?style=for-the-badge&logo=axios&logoColor=white">
</p>

## Instalação e Execução (comando único)

```bash
# Clone o repositório
git clone <url-do-seu-repositorio>
cd <nome-do-repositorio>

# Configurar backend (Laravel)
cd apiFeira
composer install
cp .env.example .env
php artisan key:generate

# Edite o .env para definir:
# DB_DATABASE=...
# DB_USERNAME=...
# DB_PASSWORD=...

php artisan migrate --seed
php artisan serve &

# Em novo terminal: configurar frontend (Vue)
cd ../feira_ciencias
npm install
npm run dev
