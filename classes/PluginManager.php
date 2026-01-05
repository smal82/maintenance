<?php
// classes/PluginManager.php

class PluginManager {
    private $db;
    private $loadedPlugins = [];
    private $hooks = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadActivePlugins();
    }
    
    private function loadActivePlugins() {
        if (!PLUGIN_ENABLED) {
            return;
        }
        
        $plugins = $this->db->fetchAll(
            "SELECT * FROM plugins WHERE is_active = 1 ORDER BY name"
        );
        
        foreach ($plugins as $plugin) {
            $this->loadPlugin($plugin);
        }
    }
    
    private function loadPlugin($pluginData) {
        $pluginPath = PLUGIN_PATH . '/' . $pluginData['folder_name'];
        $mainFile = $pluginPath . '/plugin.php';
        
        if (!file_exists($mainFile)) {
            error_log("Plugin file not found: {$mainFile}");
            return false;
        }
        
        try {
            require_once $mainFile;
            
            $className = $this->getPluginClassName($pluginData['name']);
            if (class_exists($className)) {
                $pluginInstance = new $className($pluginData);
                $this->loadedPlugins[$pluginData['name']] = $pluginInstance;
                
                if (method_exists($pluginInstance, 'init')) {
                    $pluginInstance->init();
                }
                
                return true;
            }
        } catch (Exception $e) {
            error_log("Error loading plugin {$pluginData['name']}: " . $e->getMessage());
        }
        
        return false;
    }
    
    private function getPluginClassName($pluginName) {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $pluginName))) . 'Plugin';
    }
    
    public function registerHook($hookName, $callback, $priority = 10) {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }
        
        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        
        usort($this->hooks[$hookName], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    public function executeHook($hookName, $data = null) {
        if (!isset($this->hooks[$hookName])) {
            return $data;
        }
        
        foreach ($this->hooks[$hookName] as $hook) {
            if (is_callable($hook['callback'])) {
                $data = call_user_func($hook['callback'], $data);
            }
        }
        
        return $data;
    }
    
    public function getPlugin($name) {
        return $this->loadedPlugins[$name] ?? null;
    }
    
    public function getAllPlugins() {
        return $this->db->fetchAll("SELECT * FROM plugins ORDER BY name");
    }
    
    public function installPlugin($folderName) {
        $pluginPath = PLUGIN_PATH . '/' . $folderName;
        $configFile = $pluginPath . '/config.json';
        
        if (!file_exists($configFile)) {
            throw new Exception("Plugin config.json not found");
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        
        if (!$config) {
            throw new Exception("Invalid plugin configuration");
        }
        
        $exists = $this->db->fetchOne(
            "SELECT id FROM plugins WHERE name = :name",
            ['name' => $config['name']]
        );
        
        if ($exists) {
            throw new Exception("Plugin already installed");
        }
        
        $pluginId = $this->db->insert('plugins', [
            'name' => $config['name'],
            'display_name' => $config['display_name'],
            'description' => $config['description'] ?? null,
            'version' => $config['version'] ?? '1.0.0',
            'author' => $config['author'] ?? null,
            'folder_name' => $folderName,
            'is_active' => 0,
            'config' => json_encode($config['settings'] ?? [])
        ]);
        
        $installFile = $pluginPath . '/install.php';
        if (file_exists($installFile)) {
            require_once $installFile;
        }
        
        return $pluginId;
    }
    
    public function uninstallPlugin($pluginId) {
        $plugin = $this->db->fetchOne(
            "SELECT * FROM plugins WHERE id = :id",
            ['id' => $pluginId]
        );
        
        if (!$plugin) {
            throw new Exception("Plugin not found");
        }
        
        $pluginPath = PLUGIN_PATH . '/' . $plugin['folder_name'];
        $uninstallFile = $pluginPath . '/uninstall.php';
        
        if (file_exists($uninstallFile)) {
            require_once $uninstallFile;
        }
        
        $this->db->delete('plugins', 'id = :id', ['id' => $pluginId]);
        
        return true;
    }
    
    public function activatePlugin($pluginId) {
        return $this->db->update(
            'plugins',
            ['is_active' => 1],
            'id = :id',
            ['id' => $pluginId]
        );
    }
    
    public function deactivatePlugin($pluginId) {
        return $this->db->update(
            'plugins',
            ['is_active' => 0],
            'id = :id',
            ['id' => $pluginId]
        );
    }
    
    public function updatePluginConfig($pluginId, $config) {
        return $this->db->update(
            'plugins',
            ['config' => json_encode($config)],
            'id = :id',
            ['id' => $pluginId]
        );
    }
}
?>