#Check Minecraft Account Premium in PHP

####How to work

1. Include the "class.mcpremium.php" class using: ```<?php include("./class.mcpremium.php"); ?>```

2. Instantiate the class writing: ```<?php $mcpremium=new mcpremium("USERNAME","PASSWORD"); ?>```
   where **USERNAME** is your *minecraft.net* username and **PASSWORD** is your *minecraft.net* password.
   
3. In order to check if your account is premium, write: ```<?php echo $mcpremium->isPremium(); ?>```
   if your account is premium, the output will be **true** else **false**

4. In order to check your correct username, write: ```<?php echo $mcpremium->getCorrectUsername(); ?>```

5. In order to check your uuid, write: ```<?php echo $mcpremium->getUUID(); ?>```

6. In order to check any error, write: ```<?php echo $mcpremium->getError(); ?>```

7. In order to check the raw output, write: ```<?php echo $mcpremium->getRaw(); ?>```




Visit my web site: http://www.lucapatera.it/progetti/minecraft/premium/