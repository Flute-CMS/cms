#!/bin/bash

# Определение ANSI цветов
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Определение функций
install_docker() {
    echo -e "${BLUE}Установка Docker...${NC}"
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io
    systemctl enable docker
    systemctl start docker
    echo -e "${GREEN}Docker успешно установлен${NC}"
}

install_php() {
    echo -e "${BLUE}Установка PHP...${NC}"
    apt-get update
    apt-get install -y php
    echo -e "${GREEN}PHP успешно установлен${NC}"
}

install_composer() {
    echo -e "${BLUE}Установка Composer...${NC}"
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    echo -e "${GREEN}Composer успешно установлен${NC}"
}

build() {
    echo -e "${BLUE}Вы выбрали сборку...${NC}"
    composer_install
    docker-compose build && echo -e "${GREEN}Сборка завершена успешно${NC}" || echo -e "${RED}Ошибка при сборке${NC}"
}

start() {
    echo -e "${BLUE}Вы выбрали запуск...${NC}"
    docker-compose up -d && echo -e "${GREEN}Запуск завершён успешно${NC}" || echo -e "${RED}Ошибка при запуске${NC}"
}

composer_install() {
    echo -e "${BLUE}Вы выбрали установку composer...${NC}"
    docker-compose exec php composer install && echo -e "${GREEN}Установка composer завершена успешно${NC}" || echo -e "${RED}Ошибка при установке composer${NC}"
}

print_help() {
    echo -e "${BLUE}Доступные команды:${NC}"
    echo -e "  ${GREEN}install-docker${NC} - установить Docker"
    echo -e "  ${GREEN}install-php${NC}    - установить PHP"
    echo -e "  ${GREEN}install-composer${NC}- установить Composer"
    echo -e "  ${GREEN}build${NC}          - собрать Docker образы"
    echo -e "  ${GREEN}start${NC}          - запустить Docker контейнеры"
    echo -e "  ${GREEN}composer${NC}       - выполнить composer install в контейнере php"
    echo -e "  ${GREEN}quit${NC}           - выйти из скрипта"
    echo -e "  ${GREEN}help${NC}           - показать эту справку"
}

# Вывод меню
if [ "$#" -eq 0 ]; then
    print_help
    exit 0
fi

OS="$(uname)"

case $OS in
    "Linux")
        case $1 in
            "install-docker")
                install_docker
                ;;
            "install-php")
                install_php
                ;;
            "install-composer")
                install_composer
            ;;
            "build")
                build
                ;;
            "start")
                start
                ;;
            "composer")
                composer_install
                ;;
            "help")
                print_help
                ;;
            *)
                echo -e "${RED}Неизвестная команда '$1'${NC}"
                print_help
                exit 1
            ;;
        esac
    ;;
    "Darwin")
        echo "Этот скрипт предназначен для использования на системах Linux. Для установки Docker, PHP и Composer на MacOS, пожалуйста, посмотрите следующие ссылки:"
        echo "Docker: https://docs.docker.com/docker-for-mac/install/"
        echo "PHP: https://www.php.net/manual/ru/install.macosx.php"
        echo "Composer: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos"
    ;;
    "Windows")
        echo "Этот скрипт предназначен для использования на системах Linux. Для установки Docker, PHP и Composer на Windows, пожалуйста, посмотрите следующие ссылки:"
        echo "Docker: https://docs.docker.com/docker-for-windows/install/"
        echo "PHP: https://windows.php.net/download/"
        echo "Composer: https://getcomposer.org/doc/00-intro.md#installation-windows"
    ;;
    *)
        echo "Неизвестная операционная система: $OS"
    ;;
esac