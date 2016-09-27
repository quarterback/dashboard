-- @copyright 2016 City of Bloomington, Indiana
-- @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
create table people (
	id        int unsigned not null primary key auto_increment,
	firstname varchar(128) not null,
	lastname  varchar(128) not null,
	email     varchar(255) not null,
	username  varchar(40) unique,
	password  varchar(40),
	authenticationMethod varchar(40),
	role varchar(30)
);

create table services (
    id          int unsigned not null primary key auto_increment,
    name  varchar(64 ) not null,
    class varchar(64 ) not null,
    url   varchar(128) not null
);

create table cards (
    id          int unsigned not null primary key auto_increment,
    service_id  int unsigned not null,
    description varchar(255) not null,
    method      varchar(32)  not null,
    parameters  varchar(128),
    target      tinyint      not null,
    comparison  varchar(16)  not null,
    units       varchar(16),
    constraint FK_cards_service_id foreign key (service_id) references services(id)
);

create table card_log (
    card_id   int unsigned not null,
    timestamp timestamp    not null default current_timestamp,
    value     varchar(32)  not null,
    constraint FK_card_log_card_id foreign key (card_id) references cards(id)
);
