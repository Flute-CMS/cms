#!/bin/bash

# Определение ANSI цветов
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Определение функций
install_docker() {
    echo -e "${BLUE}Installing Docker...${NC}"
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io
    systemctl enable docker
    systemctl start docker
    echo -e "${GREEN}Docker installed successfully${NC}"
}

install_php() {
    echo -e "${BLUE}Installing PHP...${NC}"
    apt-get update
    apt-get install -y php
    echo -e "${GREEN}PHP installed successfully${NC}"
}

install_composer() {
    echo -e "${BLUE}Installing Composer...${NC}"
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    echo -e "${GREEN}Composer installed successfully${NC}"
}

build() {
    echo -e "${BLUE}Building...${NC}"
    composer_install
    docker-compose build && echo -e "${GREEN}Build completed successfully${NC}" || echo -e "${RED}Error during build${NC}"
}

start() {
    echo -e "${BLUE}Starting...${NC}"
    docker-compose up -d && echo -e "${GREEN}Start completed successfully${NC}" || echo -e "${RED}Error during start${NC}"
}

composer_install() {
    echo -e "${BLUE}Installing composer...${NC}"
    docker-compose exec php composer install && echo -e "${GREEN}Composer installed successfully${NC}" || echo -e "${RED}Error during composer install${NC}"
}

print_help() {
    echo -e "${BLUE}Available commands:${NC}"
    echo -e "  ${GREEN}install-docker${NC} - install Docker"
    echo -e "  ${GREEN}install-php${NC}    - install PHP"
    echo -e "  ${GREEN}install-composer${NC}- install Composer"
    echo -e "  ${GREEN}build${NC}          - build Docker images"
    echo -e "  ${GREEN}start${NC}          - start Docker containers"
    echo -e "  ${GREEN}composer${NC}       - run composer install in php container"
    echo -e "  ${GREEN}quit${NC}           - exit from script"
    echo -e "  ${GREEN}help${NC}           - show this help"
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
                echo -e "${RED}Unknown command '$1'${NC}"
                print_help
                exit 1
            ;;
        esac
    ;;
    "Darwin")
        echo "This script is intended for use on Linux systems. For installation of Docker, PHP and Composer on MacOS, please refer to the following links:"
        echo "Docker: https://docs.docker.com/docker-for-mac/install/"
        echo "PHP: https://www.php.net/manual/ru/install.macosx.php"
        echo "Composer: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos"
    ;;
    "Windows")
        echo "This script is intended for use on Linux systems. For installation of Docker, PHP and Composer on Windows, please refer to the following links:"
        echo "Docker: https://docs.docker.com/docker-for-windows/install/"
        echo "PHP: https://windows.php.net/download/"
        echo "Composer: https://getcomposer.org/doc/00-intro.md#installation-windows"
    ;;
    *)
        echo "Unknown operating system: $OS"
    ;;
esac