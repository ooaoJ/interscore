-- ============================================================
-- InterScore - Banco de Dados Inicial
-- Sistema de gestão de campeonatos interclasse escolares
-- Banco alvo: MySQL 8+ / MariaDB recente
-- Charset: utf8mb4
--
-- Observação:
-- Se você já criou o banco pelo Laravel, pode remover as linhas
-- CREATE DATABASE e USE, e executar apenas as tabelas.
-- ============================================================

CREATE DATABASE IF NOT EXISTS interscore
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE interscore;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS ranking_rows;
DROP TABLE IF EXISTS ranking_snapshots;
DROP TABLE IF EXISTS penalties;
DROP TABLE IF EXISTS penalty_types;
DROP TABLE IF EXISTS placements;
DROP TABLE IF EXISTS match_events;
DROP TABLE IF EXISTS matches;
DROP TABLE IF EXISTS bracket_rounds;
DROP TABLE IF EXISTS brackets;
DROP TABLE IF EXISTS moderator_modalities;
DROP TABLE IF EXISTS team_students;
DROP TABLE IF EXISTS teams;
DROP TABLE IF EXISTS modality_scores;
DROP TABLE IF EXISTS modalities;
DROP TABLE IF EXISTS sports;
DROP TABLE IF EXISTS import_batches;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS classrooms;
DROP TABLE IF EXISTS competition_category_grades;
DROP TABLE IF EXISTS competition_categories;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS interclasses;
DROP TABLE IF EXISTS school_settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS schools;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. Escolas
-- ============================================================

CREATE TABLE schools (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    email VARCHAR(160) NULL,
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(50) NULL,
    logo_path VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE school_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL UNIQUE,
    show_student_full_name_public TINYINT(1) NOT NULL DEFAULT 0,
    show_student_identifier_public TINYINT(1) NOT NULL DEFAULT 0,
    allow_public_history TINYINT(1) NOT NULL DEFAULT 1,
    allow_public_team_students TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_school_settings_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Usuários administrativos
--
-- Roles:
-- platform_admin  = administrador geral da plataforma
-- school_manager  = gestor da escola
-- moderator       = moderador de modalidade
--
-- Alunos NÃO ficam aqui. Alunos ficam na tabela students.
-- Visitantes públicos não possuem login.
-- ============================================================

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('platform_admin', 'school_manager', 'moderator') NOT NULL,
    status ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_users_school_role (school_id, role),

    CONSTRAINT fk_users_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário inicial da plataforma:
-- Email: admin@interscore.local
-- Senha: admin123
-- Troque a senha no primeiro acesso.
INSERT INTO users (school_id, name, email, password, role, status, created_at)
VALUES (
    NULL,
    'Administrador da Plataforma',
    'admin@interscore.local',
    '$2y$12$9xDywyYw3T9z6YTu4vZCC.njICqEi4EdIS5Gm40AMfg7X5WwQZ0ZC',
    'platform_admin',
    'active',
    NOW()
);

-- ============================================================
-- 3. Interclasses
-- ============================================================

CREATE TABLE interclasses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    description TEXT NULL,
    regulation LONGTEXT NULL,
    banner_path VARCHAR(255) NULL,
    visibility ENUM('private', 'public') NOT NULL DEFAULT 'private',
    status ENUM(
        'draft',
        'registration_open',
        'bracket_generated',
        'in_progress',
        'finished',
        'archived',
        'cancelled'
    ) NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_interclasses_school_slug (school_id, slug),
    INDEX idx_interclasses_school_status (school_id, status),
    INDEX idx_interclasses_public (visibility, status),

    CONSTRAINT fk_interclasses_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_interclasses_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Séries e categorias competitivas
--
-- Regra inicial:
-- Categoria 1: 6º, 7º e 8º
-- Categoria 2: 9º, 1º médio, 2º médio e 3º médio
-- ============================================================

CREATE TABLE grades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    stage ENUM('elementary_2', 'high_school') NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competition_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(60) NOT NULL UNIQUE,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competition_category_grades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_category_id BIGINT UNSIGNED NOT NULL,
    grade_id BIGINT UNSIGNED NOT NULL,

    UNIQUE KEY uq_category_grade (competition_category_id, grade_id),

    CONSTRAINT fk_category_grades_category
        FOREIGN KEY (competition_category_id) REFERENCES competition_categories(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_category_grades_grade
        FOREIGN KEY (grade_id) REFERENCES grades(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO grades (name, code, stage, sort_order) VALUES
('6º ano', '6EF', 'elementary_2', 1),
('7º ano', '7EF', 'elementary_2', 2),
('8º ano', '8EF', 'elementary_2', 3),
('9º ano', '9EF', 'elementary_2', 4),
('1º médio', '1EM', 'high_school', 5),
('2º médio', '2EM', 'high_school', 6),
('3º médio', '3EM', 'high_school', 7);

INSERT INTO competition_categories (name, code, description, sort_order) VALUES
('Categoria 6º, 7º e 8º', 'fundamental_678', 'Turmas do 6º, 7º e 8º ano jogam juntas.', 1),
('Categoria 9º e Ensino Médio', 'maior_9em', 'Turmas do 9º ano, 1º médio, 2º médio e 3º médio jogam juntas.', 2);

INSERT INTO competition_category_grades (competition_category_id, grade_id)
SELECT cc.id, g.id
FROM competition_categories cc
JOIN grades g ON g.code IN ('6EF', '7EF', '8EF')
WHERE cc.code = 'fundamental_678';

INSERT INTO competition_category_grades (competition_category_id, grade_id)
SELECT cc.id, g.id
FROM competition_categories cc
JOIN grades g ON g.code IN ('9EF', '1EM', '2EM', '3EM')
WHERE cc.code = 'maior_9em';

-- ============================================================
-- 5. Turmas e alunos
-- ============================================================

CREATE TABLE classrooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    grade_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    shift ENUM('morning', 'afternoon', 'evening', 'full_time') NULL,
    course_name VARCHAR(120) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_classrooms_interclass_name (interclass_id, name),
    INDEX idx_classrooms_school_interclass (school_id, interclass_id),

    CONSTRAINT fk_classrooms_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_classrooms_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_classrooms_grade
        FOREIGN KEY (grade_id) REFERENCES grades(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    classroom_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    display_name VARCHAR(80) NULL,
    gender ENUM('M', 'F', 'O', 'N') NOT NULL DEFAULT 'N',
    school_identifier VARCHAR(60) NOT NULL,
    status ENUM('active', 'inactive', 'transferred') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_students_interclass_identifier (interclass_id, school_identifier),
    INDEX idx_students_classroom (classroom_id),
    INDEX idx_students_name (name),

    CONSTRAINT fk_students_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_students_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_students_classroom
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE import_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    classroom_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    source_type ENUM('csv', 'xlsx', 'pdf') NOT NULL,
    original_file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NULL,
    status ENUM('uploaded', 'previewed', 'imported', 'failed', 'cancelled') NOT NULL DEFAULT 'uploaded',
    total_rows INT NOT NULL DEFAULT 0,
    valid_rows INT NOT NULL DEFAULT 0,
    invalid_rows INT NOT NULL DEFAULT 0,
    error_report JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_import_batches_interclass (interclass_id, classroom_id),

    CONSTRAINT fk_import_batches_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_import_batches_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_import_batches_classroom
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_import_batches_user
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. Esportes, modalidades e pontuações
-- ============================================================

CREATE TABLE sports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sports (name, slug, status) VALUES
('Futsal', 'futsal', 'active'),
('Vôlei', 'volei', 'active'),
('Queimada', 'queimada', 'active'),
('Basquete', 'basquete', 'active'),
('Handebol', 'handebol', 'active'),
('Tênis de Mesa', 'tenis-de-mesa', 'active'),
('Xadrez', 'xadrez', 'active'),
('Atletismo', 'atletismo', 'active');

CREATE TABLE modalities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    sport_id BIGINT UNSIGNED NOT NULL,
    competition_category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    gender ENUM('male', 'female', 'mixed', 'open') NOT NULL DEFAULT 'open',
    team_type ENUM('collective', 'individual') NOT NULL DEFAULT 'collective',
    min_athletes SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    max_athletes SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    allow_draw TINYINT(1) NOT NULL DEFAULT 0,
    has_third_place TINYINT(1) NOT NULL DEFAULT 1,
    bracket_type ENUM('single_elimination', 'group_stage', 'round_robin') NOT NULL DEFAULT 'single_elimination',
    status ENUM('draft', 'open', 'bracket_generated', 'in_progress', 'finished', 'cancelled') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_modalities_interclass_name (interclass_id, name),
    INDEX idx_modalities_interclass_status (interclass_id, status),
    INDEX idx_modalities_sport_category (sport_id, competition_category_id),

    CONSTRAINT fk_modalities_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_modalities_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_modalities_sport
        FOREIGN KEY (sport_id) REFERENCES sports(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_modalities_category
        FOREIGN KEY (competition_category_id) REFERENCES competition_categories(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE modality_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modality_id BIGINT UNSIGNED NOT NULL,
    position TINYINT UNSIGNED NOT NULL,
    points DECIMAL(8,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_modality_scores_position (modality_id, position),

    CONSTRAINT fk_modality_scores_modality
        FOREIGN KEY (modality_id) REFERENCES modalities(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. Equipes e alunos por equipe
-- ============================================================

CREATE TABLE teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    classroom_id BIGINT UNSIGNED NOT NULL,
    modality_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    status ENUM(
        'draft',
        'valid',
        'blocked',
        'eliminated',
        'champion',
        'runner_up',
        'third_place'
    ) NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_teams_modality_classroom (modality_id, classroom_id),
    INDEX idx_teams_interclass (interclass_id),
    INDEX idx_teams_classroom (classroom_id),

    CONSTRAINT fk_teams_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_teams_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_teams_classroom
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_teams_modality
        FOREIGN KEY (modality_id) REFERENCES modalities(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_teams_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE team_students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    interclass_id BIGINT UNSIGNED NOT NULL,
    modality_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    participation_type ENUM('athlete', 'captain', 'reserve') NOT NULL DEFAULT 'athlete',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_team_students_team_student (team_id, student_id),
    UNIQUE KEY uq_team_students_student_modality (student_id, modality_id),
    INDEX idx_team_students_team (team_id),

    CONSTRAINT fk_team_students_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_team_students_modality
        FOREIGN KEY (modality_id) REFERENCES modalities(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_team_students_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_team_students_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. Moderadores por modalidade
--
-- Regra:
-- Um moderador pode registrar resultados apenas das modalidades
-- em que foi designado dentro de um interclasse.
-- ============================================================

CREATE TABLE moderator_modalities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    modality_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_moderator_modality (user_id, modality_id),
    INDEX idx_moderator_modalities_interclass (interclass_id),

    CONSTRAINT fk_moderator_modalities_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_moderator_modalities_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_moderator_modalities_modality
        FOREIGN KEY (modality_id) REFERENCES modalities(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_moderator_modalities_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. Chaveamentos, rodadas e partidas
-- ============================================================

CREATE TABLE brackets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    modality_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    type ENUM('single_elimination', 'group_stage', 'round_robin') NOT NULL DEFAULT 'single_elimination',
    status ENUM('generated', 'in_progress', 'finished', 'cancelled') NOT NULL DEFAULT 'generated',
    random_seed VARCHAR(100) NULL,
    generated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_brackets_modality (modality_id),
    INDEX idx_brackets_interclass (interclass_id),

    CONSTRAINT fk_brackets_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_brackets_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_brackets_modality
        FOREIGN KEY (modality_id) REFERENCES modalities(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_brackets_generated_by
        FOREIGN KEY (generated_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bracket_rounds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bracket_id BIGINT UNSIGNED NOT NULL,
    round_number INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_bracket_rounds_number (bracket_id, round_number),

    CONSTRAINT fk_bracket_rounds_bracket
        FOREIGN KEY (bracket_id) REFERENCES brackets(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    modality_id BIGINT UNSIGNED NOT NULL,
    bracket_id BIGINT UNSIGNED NULL,
    round_id BIGINT UNSIGNED NULL,
    match_number INT UNSIGNED NOT NULL,
    team_a_id BIGINT UNSIGNED NULL,
    team_b_id BIGINT UNSIGNED NULL,
    next_match_id BIGINT UNSIGNED NULL,
    next_slot ENUM('A', 'B') NULL,
    loser_next_match_id BIGINT UNSIGNED NULL,
    loser_next_slot ENUM('A', 'B') NULL,
    scheduled_at DATETIME NULL,
    location VARCHAR(160) NULL,
    score_a INT NULL,
    score_b INT NULL,
    winner_team_id BIGINT UNSIGNED NULL,
    status ENUM(
        'scheduled',
        'in_progress',
        'pending_review',
        'finished',
        'cancelled',
        'wo'
    ) NOT NULL DEFAULT 'scheduled',
    notes TEXT NULL,
    registered_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_matches_bracket_number (bracket_id, match_number),
    INDEX idx_matches_interclass_modality (interclass_id, modality_id),
    INDEX idx_matches_status_date (status, scheduled_at),
    INDEX idx_matches_teams (team_a_id, team_b_id),

    CONSTRAINT fk_matches_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_matches_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_matches_modality
        FOREIGN KEY (modality_id) REFERENCES modalities(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_matches_bracket
        FOREIGN KEY (bracket_id) REFERENCES brackets(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_matches_round
        FOREIGN KEY (round_id) REFERENCES bracket_rounds(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_matches_team_a
        FOREIGN KEY (team_a_id) REFERENCES teams(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_matches_team_b
        FOREIGN KEY (team_b_id) REFERENCES teams(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_matches_winner
        FOREIGN KEY (winner_team_id) REFERENCES teams(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_matches_next_match
        FOREIGN KEY (next_match_id) REFERENCES matches(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_matches_loser_next_match
        FOREIGN KEY (loser_next_match_id) REFERENCES matches(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_matches_registered_by
        FOREIGN KEY (registered_by) REFERENCES users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_matches_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE match_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    student_id BIGINT UNSIGNED NULL,
    event_type ENUM('score', 'penalty', 'card', 'note', 'substitution', 'wo') NOT NULL,
    event_minute VARCHAR(20) NULL,
    value DECIMAL(8,2) NULL,
    description TEXT NULL,
    registered_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_match_events_match (match_id),

    CONSTRAINT fk_match_events_match
        FOREIGN KEY (match_id) REFERENCES matches(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_match_events_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_match_events_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_match_events_registered_by
        FOREIGN KEY (registered_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. Colocações, penalidades e ranking
-- ============================================================

CREATE TABLE placements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    modality_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    classroom_id BIGINT UNSIGNED NOT NULL,
    position TINYINT UNSIGNED NOT NULL,
    points_awarded DECIMAL(8,2) NOT NULL DEFAULT 0,
    defined_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_placements_modality_position (modality_id, position),
    UNIQUE KEY uq_placements_modality_team (modality_id, team_id),
    INDEX idx_placements_interclass_classroom (interclass_id, classroom_id),

    CONSTRAINT fk_placements_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_placements_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_placements_modality
        FOREIGN KEY (modality_id) REFERENCES modalities(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_placements_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_placements_classroom
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_placements_defined_by
        FOREIGN KEY (defined_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE penalty_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    default_points DECIMAL(8,2) NOT NULL DEFAULT 0,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_penalty_types_school (school_id, active),

    CONSTRAINT fk_penalty_types_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO penalty_types (school_id, name, description, default_points, severity, active) VALUES
(NULL, 'Atraso para partida', 'Equipe atrasou o início da partida.', -1, 'low', 1),
(NULL, 'Conduta antidesportiva', 'Atitude inadequada durante a competição.', -3, 'medium', 1),
(NULL, 'W.O.', 'Equipe não compareceu à partida.', -5, 'high', 1),
(NULL, 'Briga ou agressão', 'Ocorrência grave envolvendo conflito físico ou agressão.', -10, 'critical', 1);

CREATE TABLE penalties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    modality_id BIGINT UNSIGNED NULL,
    match_id BIGINT UNSIGNED NULL,
    team_id BIGINT UNSIGNED NULL,
    classroom_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NULL,
    penalty_type_id BIGINT UNSIGNED NOT NULL,
    points DECIMAL(8,2) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'applied', 'cancelled') NOT NULL DEFAULT 'applied',
    registered_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_penalties_interclass_classroom (interclass_id, classroom_id),
    INDEX idx_penalties_team (team_id),
    INDEX idx_penalties_match (match_id),

    CONSTRAINT fk_penalties_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_penalties_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_penalties_modality
        FOREIGN KEY (modality_id) REFERENCES modalities(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_penalties_match
        FOREIGN KEY (match_id) REFERENCES matches(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_penalties_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_penalties_classroom
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_penalties_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_penalties_type
        FOREIGN KEY (penalty_type_id) REFERENCES penalty_types(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_penalties_registered_by
        FOREIGN KEY (registered_by) REFERENCES users(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_penalties_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ranking_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    interclass_id BIGINT UNSIGNED NOT NULL,
    generated_by BIGINT UNSIGNED NULL,
    generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,

    INDEX idx_ranking_snapshots_interclass (interclass_id, generated_at),

    CONSTRAINT fk_ranking_snapshots_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ranking_snapshots_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ranking_snapshots_generated_by
        FOREIGN KEY (generated_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ranking_rows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ranking_snapshot_id BIGINT UNSIGNED NOT NULL,
    classroom_id BIGINT UNSIGNED NOT NULL,
    position INT UNSIGNED NOT NULL,
    modality_points DECIMAL(8,2) NOT NULL DEFAULT 0,
    penalty_points DECIMAL(8,2) NOT NULL DEFAULT 0,
    total_points DECIMAL(8,2) NOT NULL DEFAULT 0,
    first_places INT UNSIGNED NOT NULL DEFAULT 0,
    second_places INT UNSIGNED NOT NULL DEFAULT 0,
    third_places INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_ranking_rows_snapshot_classroom (ranking_snapshot_id, classroom_id),
    INDEX idx_ranking_rows_position (ranking_snapshot_id, position),

    CONSTRAINT fk_ranking_rows_snapshot
        FOREIGN KEY (ranking_snapshot_id) REFERENCES ranking_snapshots(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ranking_rows_classroom
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. Auditoria
--
-- Registrar ações importantes:
-- criação/edição de interclasse, geração de chaveamento,
-- alteração de resultado, aplicação/cancelamento de penalidade etc.
-- ============================================================

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NULL,
    interclass_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(120) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_audit_logs_school_interclass (school_id, interclass_id),
    INDEX idx_audit_logs_user (user_id),
    INDEX idx_audit_logs_entity (entity_type, entity_id),

    CONSTRAINT fk_audit_logs_school
        FOREIGN KEY (school_id) REFERENCES schools(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_audit_logs_interclass
        FOREIGN KEY (interclass_id) REFERENCES interclasses(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. Views úteis para consultas
-- ============================================================

CREATE OR REPLACE VIEW vw_public_matches AS
SELECT
    m.id,
    s.slug AS school_slug,
    i.slug AS interclass_slug,
    i.name AS interclass_name,
    mo.name AS modality_name,
    br.name AS bracket_name,
    r.name AS round_name,
    m.match_number,
    ta.name AS team_a_name,
    tb.name AS team_b_name,
    m.score_a,
    m.score_b,
    tw.name AS winner_team_name,
    m.scheduled_at,
    m.location,
    m.status
FROM matches m
JOIN schools s ON s.id = m.school_id
JOIN interclasses i ON i.id = m.interclass_id
JOIN modalities mo ON mo.id = m.modality_id
LEFT JOIN brackets br ON br.id = m.bracket_id
LEFT JOIN bracket_rounds r ON r.id = m.round_id
LEFT JOIN teams ta ON ta.id = m.team_a_id
LEFT JOIN teams tb ON tb.id = m.team_b_id
LEFT JOIN teams tw ON tw.id = m.winner_team_id
WHERE i.visibility = 'public'
  AND s.status = 'active';

CREATE OR REPLACE VIEW vw_interclass_ranking_current AS
SELECT
    rs.interclass_id,
    rr.classroom_id,
    c.name AS classroom_name,
    rr.position,
    rr.modality_points,
    rr.penalty_points,
    rr.total_points,
    rr.first_places,
    rr.second_places,
    rr.third_places,
    rs.generated_at
FROM ranking_rows rr
JOIN ranking_snapshots rs ON rs.id = rr.ranking_snapshot_id
JOIN classrooms c ON c.id = rr.classroom_id
WHERE rs.id = (
    SELECT MAX(rs2.id)
    FROM ranking_snapshots rs2
    WHERE rs2.interclass_id = rs.interclass_id
);

-- ============================================================
-- Fim do SQL inicial InterScore
-- ============================================================
