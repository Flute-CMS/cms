> [!IMPORTANT]
> Версия **2.0** находится в разработке **прямо сейчас**, поэтому обновления текущей версии приостановлены. Всю информацию можно найти в нашем [Discord](https://discord.gg/flute).

<div align="center">
  
[<kbd><br>🌐 Русский README<br><br></kbd>](./README_RU.md)
[<kbd><br>🌐 English README<br><br></kbd>](./README.md)
[<kbd><br>🌐 Português README<br><br></kbd>](./README_BR.md)
</div>

<hr />
&nbsp;
<p align="center">
  <a href="https://flute-cms.com" target="_blank">
    <img src="https://github.com/Flute-CMS/cms/assets/62756604/af601b07-7ec6-45df-8a03-592d362a4a0c" alt="Flute" width="200px">
  </a>
</p>
&nbsp;

<br />
<br />
<p align="center">
  <a href="https://demo.flute-cms.com">🌍 Демо Flute</a> •
    <a href="https://docs.flute-cms.com">📖 Документация</a> •
    <a href="https://discord.gg/BcBMeVJJsd">💬 Discord</a>
    <br /><br />
   <a href="https://github.com/Flute-CMS/cms/releases">
        <img src="https://img.shields.io/github/release/Flute-CMS/cms.svg" alt="Последний релиз" />
    </a>
  &nbsp;
  <a href="https://discord.gg/BcBMeVJJsd"><img alt="Discord" src="https://img.shields.io/discord/869991184968323092?label=Discord&color=7289da&style=flat-square" /></a>
  &nbsp;
</p>
&nbsp;

<hr />

<a href="https://demo.flute-cms.com">
  <img src="https://github.com/Flute-CMS/cms/assets/62756604/81f45ad7-f065-4248-b946-94f01312a3cc" alt="Flute админ-панели"/>
</a>
<p align="center">
  👀 Пример админ-панели
</p>

<hr />
<b>Flute</b> - это комплексное решение для серверов CS2, CS:S, Minecraft и других игровых серверов. Распространяется как Open Source, Flute позволяет вам установить его и использовать на вашем веб-хостинге абсолютно бесплатно! 🎉

<hr />

<h3>🚀 Преимущества по сравнению с существующими решениями:</h3>
<ul>
  <li>💯 Полностью бесплатен. Для установки Flute не нужно ничего платить.</li>
  <li>🏠 Независимость от внешних сервисов. Все работает на вашем собственном сервере.</li>
  <li>🌟 Современный код. Забудьте о устаревшем PHP5!</li>
  <li>🛠️ Кастомизация. Flute позволяет вам создавать и редактировать страницы через админ-панель!</li>
  <li>📈 Полная расширяемость. Поддержка неограниченного количества модулей и шаблонов!</li>
  <li>🔗 Дружественность к Laravel. Flute будет понятен любому, кто знаком с Laravel.</li>
  <li>🔧 Обширные функции, включая различные методы авторизации, настройки, платежные системы и многое другое для упрощения использования Flute!</li>
</ul>

&nbsp;

# 💼 Требования

Для успешной установки и работы Flute убедитесь, что ваша система соответствует следующим требованиям:
-   PHP версии 7.4 или выше.
-   MySQL версии 5.7.29 или выше / MariaDB версии 10.2.7 или выше.
-   Веб-сервер Apache или Nginx.
-   (По желанию) Composer для управления зависимостями.

&nbsp;

# 🚀 Установка Flute

### На VDS (Виртуальном Выделенном Сервере):

1. Скачайте Flute из [releases](https://github.com/Flute-CMS/cms/releases).
2. Загрузите файлы на сервер.
3. Используйте команду:
    ```
    composer install
    ```
    для установки зависимостей.
4. Настройте ваш веб-сервер (Apache/Nginx) и базу данных.

&nbsp;
### На Шаред-Хостинге:
> [!TIP]
> Вы можете посмотреть видео-установку [тут](https://www.youtube.com/watch?v=PCSjl2w7A9k)

1. Скачайте Flute и папку `vendor` из [releases](https://github.com/Flute-CMS/cms/releases).
2. Загрузите их на хостинг через FTP или файловый менеджер.
3. Настройте веб-сервер на хостинге, чтобы указывать на папку Flute.

![gif_install](https://github.com/Flute-CMS/cms/assets/62756604/62b8a0cb-c7ed-431b-981c-470304c1fbd8)

Обе установки требуют настройки базы данных и конфигурации Flute.

&nbsp;

📚 Посетите нашу [документацию](https://docs.flute-cms.com/docs/what_it) для подробных инструкций по установке, настройке и разработке модулей и шаблонов.

&nbsp;

# 👨‍💻 Могу реализовать проект

Если вам нужен опытный разработчик для ваших проектов (мой стек - React / TS / Node / PHP / JS), свяжитесь со мной:
-   Discord - <kbd>flamesina</kbd>
-   [Telegram](https://t.me/flamesina)

&nbsp;

# 📦 Список Бесплатных Модулей

Для Flute существуют бесплатные модули. Список основных представлен ниже:
-   [Новости](https://github.com/Flute-CMS/news): Позволяет создавать новости в Flute
-   [Мониторинг](https://github.com/Flute-CMS/monitoring): Получает информацию о серверах и отображает ее в виджете
-   [Баны и муты](https://github.com/Flute-CMS/BansComms): Отображает список банов и мутов на отдельной странице
-   [Карусель](https://github.com/Flute-CMS/carousel): Добавляет виджет карусели
-   [Статистика](https://github.com/Flute-CMS/stats): Создает отдельную страницу со статистикой
-   ...и многое другое на нашем [github](https://github.com/orgs/Flute-CMS/repositories).

Эти модули разработаны для улучшения вашего опыта работы с Flute и предоставляются бесплатно.

&nbsp;

# 🆘 Нужна помощь?

Нужна помощь с установкой, настройкой или разработкой? Присоединяйтесь к нашему [Discord](https://discord.gg/BcBMeVJJsd) для получения помощи! Если у вас возникли проблемы или ошибки, сообщите о них:
-   [GitHub Issues](https://github.com/Flute-CMS/cms/issues)
-   [Discord](https://discord.gg/BcBMeVJJsd)

&nbsp;

# ⭐ Вам нравится Flute? Поставьте нам звезду!

![flute-gif](https://github.com/Flute-CMS/cms/assets/62756604/87d18227-41ac-4a7d-9210-d46b9fd56049)
