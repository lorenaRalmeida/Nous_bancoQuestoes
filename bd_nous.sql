create database if not exists bd_nous;
use bd_nous;

create table if not exists usuarios (
    id int auto_increment primary key,
    nome varchar(100) not null,
    email varchar(100) not null unique,
    senha varchar(255) not null,
    data_criacao timestamp default current_timestamp
);