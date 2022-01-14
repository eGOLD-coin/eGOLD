# eGOLD
---
[English]
---

Wallet eGOLD.html in English are in the folder: /en
To work node, you will need: egold.php, egold_settings.php, folder with content egold_temp and folder with content egold_crypto
The site requires an installed node and an index.html file with a folder and its contents egold_images

---
[Russian]
---

Кошелёк eGOLD.html на русском языке находятся в папке: /ru
Для работы ноды потребуется: egold.php, egold_settings.php, папка с содержимым egold_temp и папка с содержимым egold_crypto
Для сайта нужна установленная нода и файл index.html с папкой и её содержимым egold_images

---
[English]
---

//NODE SETTING
//Node requires IP address and establishing MySQL database. IP address and data from database should be added to the file: egold_settings.php
//The root folder of web-hosting addressed by IP should store files and folders: egold.php, egold_settings.php, egold_crypto
//After placing files and folders and adjusting setting to egold_settings.php, you should address egold.php script via browser using http://[ip_address_node]/egold.php check if the installation is successful. In this case you get a message with install_bd parameter marked true.
//To run the node add egold.php with synch parameter in the form of cron '/[folder path with php]/php ~/[folder path with egold.php]/egold.php synch' at a performing interval of 1 per minute (usually indicated by asterisks for all job periods in cron). PHP script corn version should be at least 7.1. Alternative usage: http://[ip_address_node]/egold.php?type=synch (not recommended)
//Once the synchronization is completed, in request http://[ip_address_node]/egold.php, datelasttransaction parameter will surpass zero. Synchronization is completed when the datelasttransaction parameter value becomes equal or close to datelasttransaction parameter of synchronized nodes.

$noda_ip= ""; //Node IP address
$host_db = "localhost";//Database server address in most cases localhost
$database_db = "";//Database name
$user_db = "";//Database user name
$password_db = "";//Database password
$prefix_db = "";//Prefix should consist up to 10 characters of English large, small letters and numbers to protect the database (eGOLD by default). For added security, set an arbitrary. 

//In case node is tied to a wallet it adds +1% to growth in coin amount on a certain wallet and 1 coin from each node transaction. Node activation requires making any transaction through the node via certain wallet after its synchronization with other nodes. In the process, in requesting http://[ip_address_node]/egold.php holder will be indicated with the number of the tied wallet. In a few minutes after the synchronization in requesting http://[ip_address_node]/egold.php?type=wallet&wallet=[number of related wallet] at a current node and at synchronized nodes in nodawallet parameter the IP address of the wallet’s node will be shown and the official eGOLD.html will display G+ bonus next to the balance, and the settings will demonstrate a line with IP address of the node.
//Assessment of G+ bonus is carried out during any inbound and outbound transaction of a wallet tied to node as well as during enrolment of percentage at a wallet, and actually this is 5% assessment instead of 4%. The assessment of bonus also occurs at any transaction within a wallet’s node but at least once every 24 hours if G+balance accounts 1 or more coins as each transaction within a node brings the wallet tied to it 1 coin, and this coin immediately goes to the account of the wallet. This is done so that the assessment of percentages on a wallet’s account would not be interfered with numerous transactions on node. 
//Any transaction sent from a wallet tied to a certain node and directed to some other node deactivates the node. If a node comprises less than 100 coins it will not be taken into account while voting for the transaction verification and its IP may be tied to another wallet, and there will be no its instant synchronization with other nodes. In order to maintain the node’s operating condition, at least one transaction a month should pass through the node, and the wallet balance should be at least 100 coins.

//Number of a node’s wallet should be of the following type: G-1000-00000-0000-00000 or 100000000000000000
$noda_wallet= "";
//$noda_wallet= "G-1000-00000-0000-00000";//Example

//Proper operation of a node requires adding at least 3 trusted nodes for initial data loads. After the initial data loads nodes will be taken from database. Nodes can also be added below line by line: $noda_trust[]= "ip_address_node";
//The list of nodes can be obtained from any trusted node request in the address bar of the browser: http://[ip_address_or_site_trusted_node]/egold.php?type=nodas from parameter "noda"
$noda_trust[]= "";
$noda_trust[]= "";
$noda_trust[]= "";
//$noda_trust[]= "91.106.203.179";//Example of using IPv4 node address

//IP addresses that are allowed multiple requests in a short period of time without blocking (checking for DOS attacks).
//You can add trusted IPs below line by line: $ip_trust[]= "ip_address";
$ip_trust[]= "";
//$ip_trust[]= "91.106.203.179";//Example of using an address IPv4

//The transaction history is cleared when any of the following conditions are met:
$history_day= 365;//Specified in days by an integer of at least 7. If less, then 7 days.
$history_size= 0;//Specified in the number of transactions with an integer of at least 1. If less, it is not taken into account.
//Transaction retention period cannot be less 1 day, even if the specified number of transactions is exceeded. The number of transactions to delete is configured, not the size in megabytes, due to the impossibility of accurately determining the exact size of the database at any given time due to compression, indexing and caching in MySQL. However, the quantity helps to empirically find the correct size of the database.

//In order to set automatic sending of notifications to emails according to transactions of users dealing with a certain node you should link your domain to hosting and specify it below, e.g. egold.pro. Hosting should support php function mail(). For proper email notification system operating, you need a domain not higher than of a second level. What is it and how to get it you may find on the Internet.
//To verify support for the mail() function, after the domain fits into the $email_domain parameter, make a request in the browser http://[ip_ address_nodes]/egold.php and if there is a parameter email_domain with your domain, then the function works.
//Further, in the settings on the wallet eGOLD.html you need to create or save a password to access the node. The fields with the field to enter E-mail will then appear. We need to fill it in and press the save button below. After any incoming or outgoing transaction, a notification will come to the saved E-mail.
$email_domain= "";
//$email_domain= "egold.pro";//Example

//Limit of messages being sent at once for inbound and outbound transactions. In case 10 messages are determined, it means 10 for incoming and outgoing messages. If there are too many messages at once, server may block their sending and mail services may consider them as spam.
$email_limit= 10;

//Delay prior to sending message to email is calculated in seconds so that message gets to addressee. Otherwise, mail services or node server may block it.
$email_delay= 0.1;

//Domain which can be used for addressing node for information is indicated with http or https, and this can be done via IP. Example: https://www.egold.pro
//To check the configuration of your site on the node, after you fit the site into the $noda_site parameter, make a request in the browser [site from the $noda_site parameter]/egold.php and if there is a parameter noda_site with your site, then the site is added.
//In any case, node operating requires IP address
$noda_site= "";
//$noda_site= "https://www.egold.pro";//Example

$fast_first_synch_bd= 1;//If=1 or parameter is missing, then at the first synchronization, the node loads the database from one random node specified in $noda_trust

---
[Russian]
---

//УСТАНОВКА НОДЫ
//Для ноды нужен IP адрес, а также нужно создать MySQL базу данных. IP адрес и данные по базе данных нужно внести в этот файл: egold_settings.php
//В корневой папке размещения ноды, по IP которого будем обращаться должны лежать файлы и папки: egold.php, egold_settings.php, egold_crypto
//После размещения файлов  и папок и внесения настроек в egold_settings.php, нужно обратиться к скрипту egold.php через браузер по адресу http://[ip_адрес_ноды]/egold.php и посмотреть, что установка прошла успешна. В этом случае выдаётся сообщение в котором будет параметр install_bd со значением true.
//Для работы ноды необходимо добавить egold.php с параметром synch в cron вида '/[путь папки с php]/php ~/[путь папки с egold.php]/egold.php synch' с периодичснотью исполнения раз в 1 минуту (обычно обозначается звёздочками для всех периодов задания в cron). Версия исполнения PHP скрипта в кроне должна быть от 7.1. Можно использовать и так: http://[ip_адрес_ноды]/egold.php?type=synch (не рекомендуется)
//После выполнения синхронизации, при запросе http://[ip_адрес_ноды]/egold.php, параметр datelasttransaction станет больше нуля. Синхронизация считается завершённой, когда число параметра datelasttransaction станет равно или близко к параметру datelasttransaction других синхронизированных нод.

$noda_ip= "";//IP адрес ноды
$host_db = "localhost";//Адрес сервера базы данных в большинстве случаев localhost
$database_db = "";//Имя базы данных
$user_db = "";//Имя пользователя базы данных
$password_db = "";//Пароль базы данных
$prefix_db = "";//Префикс до 10 символов только из английских больших и маленьких букв и цифр для защиты базы данных (по умолчанию eGOLD). Лучше для безопасности, задать свой произвольный.

//Если нода привязана к кошельку, это даёт +1% к росту монет на указанном кошельке и 1 монету с каждой транзакции по ноде. Для работы ноды необходимо совершить любую операцию через данную ноду с помощью данного кошелька после её синхронизации с остальными нодами. При этом, при запросе http://[ip_адрес_ноды]/egold.php параметр owner будет с номером привязанного кошелька. Через несколько минут, после синхронизации, при запросе http://[ip_адрес_ноды]/egold.php?type=wallet&wallet=[номер привязанного кошелька] в текущей ноде и уже синхронизированных нодах в параметре nodawallet появится IP адрес ноды кошелька и в официальном кошельке eGOLD.html отобразится бонус G+ рядом с балансом и в настройках будет строка с IP адресом ноды.
//Зачисление бонуса G+ осуществляется при любой входящей или исходящей транзакции кошелька, привязанного к ноде, также как при зачислении процентов по кошельку, фактически это и есть зачисление 5%, вместо 4%. Зачисление бонуса также происходит при любой транзакции по ноде кошелька, но не реже 1-ого раза в 24 часа, если на балансе G+ за это время накопилось 1-а или более монет, так как любая транзакция по ноде приносит кошельку ноды 1-у монету и она сразу зачисляется на баланс кошелька. Это сделано для того, чтобы накопление процентов с баланса кошелька не сбивалось многочисленными транзакциями по ноде.
//Любая транзакция с кошелька, привязанного к ноде, произведённая в другую ноду, деактивирует ноду. Если на ноде будет меньше 100 монет, она не будет учитываться при голосовании за верность транзакции и её IP может привязать к себе другой кошелёк, также не будет происходить её мгновенная синхронизация с другими нодами. Чтобы нода находилась в рабочем состоянии, через эту ноду должна проходить, не реже 1-ого раза в месяц, хотя бы 1-а транзакция и баланс на кошельке ноды должен быть не менее 100 монет.

//Номер кошелька для ноды должен быть вида: G-1000-00000-0000-00000 или 100000000000000000
$noda_wallet= "";
//$noda_wallet= "G-1000-00000-0000-00000";//Пример

//Для корректной работы ноды нужно добавить от 3 доверенных нод для первичной загрузки данных. После первичной загрузки, ноды уже будут браться из базы данных. Также можно добавить ещё ноды ниже построчно: $noda_trust[]= "ip_адрес_ноды";
//Список нод можно получить с любой доверенной ноды запросом в адресной строке браузера: http://[ip_адрес_или_сайт_доверенной_ноды]/egold.php?type=nodas из параметра "noda"
$noda_trust[]= "";
$noda_trust[]= "";
$noda_trust[]= "";
//$noda_trust[]= "91.106.203.179";//Пример использования IPv4 адреса ноды

//IP адреса, которым разрешены многочисленные запросы в короткий промежуток времени без блокировки (проверки на DOS атаки).
//Можно добавить доверенные IP ниже построчно: $ip_trust[]= "ip_адрес";
$ip_trust[]= "";
//$ip_trust[]= "91.106.203.179";//Пример использования адреса IPv4

//Очистка истории транзакций происходит при достижении любого из условий:
$history_day= 365;//В днях целым числом от 7 дней. Если меньше, то 7 дней.
$history_size= 0;//В транзакциях целым числом от 1. Если меньше, то не учитывается.
//Срок хранения транзакции не может быть менее 1 дня, даже если заданное количество транзакций превышено. Для удаления настраивается количество транзакций, а не размер в мегабайтах, по причине невозможности точного определения в каждый момент времени размера базы данных из-за сжатия, индексации и кэширования в MySQL. Однако, количество строк помогает опытным путём найти нужный размер базы данных.

//Чтобы сделать автоматическую отправку уведомлений на электронную почту по транзакциям пользователей, использующим текущую ноду, нужно привязать к хостингу свой домен и указать его ниже, например egold.pro. Хостинг должен поддерживать php функцию mail(). Для работы почты, потребуется свой домен не выше второго уровня. Что это такое и где его получить, можно найти в интернете.
//Чтобы проверить поддержку функции mail(), после вписывания домена в параметр $email_domain, сделайте запрос в браузере http://[ip_адрес_ноды]/egold.php и если присутствует параметр email_domain с Вашим доменом, значит функция работает.
//Далее, в настройках на кошельке eGOLD.html нужно создать или сохранить пароль для доступа к ноде. После этого появятся поля с полем для ввода E-mail почты. Нужно её заполнить и нажать кнопку сохранить ниже. После любой входящей или исходящей транзакции на сохранённую почту придёт уведомление.
$email_domain= "";
//$email_domain= "egold.pro";//Пример

//Лимит отправляемых писем за раз для входящих и исходящих транзакций. Если установлено 10, то для исходящих 10 писем и для входящих 10. При большом количестве писем за раз, сервер может заблокировать их отправку и почтовые сайты могут посчитать это спамом.
$email_limit= 10;

//Задержка перед отправкой письма на электронную почту в секундах, чтобы письма доходили до адресата. Иначе, может быть блокировка со стороны почтовых сайтов или сервера, где расположена нода
$email_delay= 0.1;

//Домен вместе с http или https, по которому разрешено обращаться к ноде для информации, также как по IP. Например: https://www.egold.pro
//Чтобы проверить настройку Вашего сайта на ноде, после вписывания сайта в параметр $noda_site, сделайте запрос в браузере [сайт из параметра $noda_site]/egold.php и если присутствует параметр noda_site с Вашим сайтом, значит сайт добавлен.
//В любом случае, для работы ноды нужен IP адрес
$noda_site= "";
//$noda_site= "https://www.egold.pro";//Пример

$fast_first_synch_bd= 1;//Если=1 или параметр отсутствует, то при первой синхронизации нода загружает базу данных с одной случайной ноды указанной в $noda_trust

---
[English]
---

To make the site work, just copy index.html and the egold_images folder with its contents. The site requires its own node and the index.html file itself with the folder egold_images must lie next to the egold.php file and in the same folder.

The site should be opened by IP nodes http://[ip_address_node] and if this does not happen, you need to check the opening of the site using the link http://[ip_address_node]/index.html If the page has opened, try renaming index.html to index.php and reopen http://[ip_address_node]


---
[Russian]
---

Чтобы сайт заработал, достаточно скопировать index.html и папку egold_images с её содержимым. Для работы сайта необходима своя нода а сам файл index.html с папкой  egold_images должны лежать рядом с файлом ноды egold.php и в той же папке.

Сайт должен открываться по IP ноды http://[ip_адрес_ноды] и если этого не происходит, нужно проверить открытие сайта по ссылке http://[ip_адрес_ноды]/index.html Если страница открылась, попробуйте переименовать index.html в index.php и ещё раз открыть http://[ip_адрес_ноды]


---
[English]
---

Licensed GNU GPL

---
[Russian]
---

Лицензия GNU GPL
