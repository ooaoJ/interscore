# InterScore

<p align="center">
  <img src="docs/logo.png" alt="InterScore Logo" width="250">
</p>

<p align="center">
  Plataforma para organização e gestão de interclasses escolares.
</p>

---

## Sobre o Projeto

O InterScore é uma plataforma desenvolvida para auxiliar escolas na organização e gerenciamento de campeonatos interclasse.

A aplicação centraliza todas as etapas da competição, desde o cadastro de turmas e alunos até a definição dos campeões e cálculo do ranking geral.

O objetivo é reduzir processos manuais, facilitar a organização dos eventos esportivos e fornecer transparência para alunos, professores e gestores.

---

## Funcionalidades

### Administração

- Cadastro de escolas
- Gestão de usuários
- Controle de permissões
- Configuração de interclasses

### Turmas e Alunos

- Cadastro de turmas
- Cadastro manual de alunos
- Importação de alunos por arquivo
- Controle por série e categoria

### Modalidades

- Cadastro de modalidades esportivas
- Configuração de categorias
- Definição de pontuação

### Equipes

- Criação de equipes por turma
- Seleção de atletas
- Controle de participação

### Competições

- Geração automática de chaveamentos
- Controle de partidas
- Registro de resultados
- Definição de campeões

### Ranking

- Pontuação por modalidade
- Aplicação de penalidades
- Classificação geral das turmas

### Portal Público

- Consulta de resultados
- Consulta de chaveamentos
- Ranking das turmas
- Histórico de interclasses
- Campeões por modalidade

---

## Perfis de Usuário

### Administrador da Plataforma

Responsável pela administração geral do sistema.

### Gestor Escolar

Responsável pela organização do interclasse da escola.

### Moderador

Responsável por modalidades específicas, podendo registrar resultados e acompanhar partidas.

### Alunos

Acessam apenas o portal público para acompanhar as competições.

---

## Tecnologias Utilizadas

### Backend

- PHP
- Laravel
- Laravel Sanctum
- MySQL

### Frontend

- Vue 3
- Vue Router
- Bootstrap

### Infraestrutura

- Apache
- Linux

---

## Estrutura do Projeto

```txt
Backend
├── Laravel
├── API REST
├── Sanctum
└── MySQL

Frontend
├── Vue 3
├── Vue Router
└── Bootstrap
```

---

## Roadmap

### Fase Atual

- [x] Modelagem do banco de dados
- [x] Migrations
- [x] Models
- [x] Seeders
- [x] Autenticação com Sanctum
- [ ] Controle de permissões
- [ ] CRUD de escolas
- [ ] CRUD de usuários

### Próximas Etapas

- [ ] CRUD de interclasses
- [ ] Cadastro de turmas
- [ ] Cadastro de alunos
- [ ] Cadastro de modalidades
- [ ] Cadastro de equipes
- [ ] Chaveamento automático
- [ ] Registro de resultados
- [ ] Ranking geral
- [ ] Portal público

---

## Instalação

Clone o projeto:

```bash
git clone https://github.com/ooaoJ/interscore.git
```

Entre na pasta:

```bash
cd interscore
```

Instale as dependências:

```bash
composer install
```

Configure o ambiente:

```bash
cp .env.example .env
php artisan key:generate
```

Configure o banco de dados no arquivo `.env`.

Execute as migrations:

```bash
php artisan migrate --seed
```

Inicie o servidor:

```bash
php artisan serve
```

---

## Licença

Este projeto está em desenvolvimento e atualmente possui fins educacionais e de portfólio.
