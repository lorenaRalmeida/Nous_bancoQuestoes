CREATE DATABASE bd_nous CHARACTER SET utf8mb4;
USE bd_nous;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL
);

CREATE TABLE favoritos (
    usuario_id INT NOT NULL,
    questao_id VARCHAR(64) NOT NULL,
    banca VARCHAR(16) NOT NULL,
    PRIMARY KEY (usuario_id, questao_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE progresso (
    usuario_id INT NOT NULL,
    questao_id VARCHAR(64) NOT NULL,
    banca VARCHAR(16) NOT NULL,
    resposta CHAR(1) NOT NULL,
    acertou TINYINT NOT NULL,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, questao_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE tarefas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'pendente',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE compromissos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    data_compromisso DATE NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
