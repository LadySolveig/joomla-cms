INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `locked`, `manifest_cache`, `params`, `custom_data`, `ordering`, `state`)
SELECT 0, 'plg_system_commands', 'plugin', 'commands', 'system', 0, 1, 1, 0, 1, '', '{"openhotkey":"cmd+k,ctrl+k"}', '', -1, 0
WHERE NOT EXISTS (SELECT * FROM `#__extensions` e WHERE e.`type` = 'plugin' AND e.`element` = 'commands' AND e.`folder` = 'system' AND e.`client_id` = 0);
