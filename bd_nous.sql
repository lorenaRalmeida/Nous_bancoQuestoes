-- tabela do usuario
create table if not exists usuarios (
    id int auto_increment primary key,
    nome varchar(100) not null,
    email varchar(100) not null unique,
    senha varchar(255) not null
);

-- tabela da agenda
create table if not exists compromissos (
    id int auto_increment primary key,
    usuario_id int not null,
    titulo varchar(150) not null,
    data_compromisso date not null,
    data_criacao timestamp default current_timestamp,
    foreign key (usuario_id) references usuarios(id) on delete cascade
);

-- tabela de tarefa
create table if not exists tarefas (
    id int auto_increment primary key,
    usuario_id int not null,
    titulo varchar(255) not null,
    status varchar(20) default 'pendente',
    data_criacao timestamp default current_timestamp,
    foreign key (usuario_id) references usuarios(id) on delete cascade
);
