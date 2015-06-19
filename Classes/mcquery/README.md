# PHP Minecraft Query

## Description
This class was created to query Minecraft servers.<br>
It works starting from **Minecraft 1.0**

## Instructions
Before using this class, you need to make sure that your server is running GS4 status listener.

Look for those settings in **server.properties**:

> *enable-query=true*<br>
> *query.port=25565*

### How to works

1. Make sure you have the UDP port open associated with the query server.
2. Include the "class.mcquery.php" class using: ```<?php include("./class.mcquery.php"); ?>```
3. Instanziate the class writing: ```<?php $status=new mcquery("SERVER IP","SERVER PORT"); ?>```
4. Get a given data using: ```<?php $status->functionname(); >```

Here a table with the available functions:
   
| Data | Type | Function |
| ------------- | ------------- | ------------- |
| Online | bool | isOnline() |
| Motd |string | getMOTD() |
| Game Type | string | getGameType() |
| Version | string | getVersion() |
| Plugins | array string | getPlugins() |
| Map | string | getMap() |
| Online Player | int | getOnlinePlayer() |
| Max Player | int | getMaxPlayer() |
| Host IP | string | getHostIp() |
| Host Port | int | getHostPort() |
| Game Name | string | getGameName() |
| Software | string | getSoftware() |
| Players | array string | getPlayers() |
| Raw Output | array object | getRaw() |
| Errors | string | getErrors() |


Visit my web site: http://www.lucapatera.it/progetti/minecraft/query
