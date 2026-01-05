<?php
// plugins/example-plugin/plugin.php
/**
 * Example Plugin
 * 
 * Questo è un esempio di come creare un plugin per il sistema
 */

class ExamplePlugin {
    private $pluginData;
    private $db;
    
    public function __construct($pluginData) {
        $this->pluginData = $pluginData;
        $this->db = Database::getInstance();
    }
    
    /**
     * Inizializzazione del plugin
     * Chiamato quando il plugin viene caricato
     */
    public function init() {
        // Registra hooks
        global $pluginManager;
        
        // Hook esempio: modifica il titolo della dashboard
        $pluginManager->registerHook('dashboard_title', [$this, 'modifyDashboardTitle'], 10);
        
        // Hook esempio: aggiunge un widget alla dashboard
        $pluginManager->registerHook('dashboard_widgets', [$this, 'addDashboardWidget'], 10);
        
        // Hook esempio: azione dopo il salvataggio di una manutenzione
        $pluginManager->registerHook('maintenance_saved', [$this, 'onMaintenanceSaved'], 10);
    }
    
    /**
     * Modifica il titolo della dashboard
     */
    public function modifyDashboardTitle($title) {
        return $title . ' - Plugin Attivo';
    }
    
    /**
     * Aggiunge un widget alla dashboard
     */
    public function addDashboardWidget($widgets) {
        $widgets[] = [
            'title' => 'Widget Plugin Esempio',
            'content' => $this->renderWidget(),
            'priority' => 10
        ];
        return $widgets;
    }
    
    /**
     * Azione dopo il salvataggio di una manutenzione
     */
    public function onMaintenanceSaved($maintenanceData) {
        // Esempio: invia notifica personalizzata
        error_log("Plugin: Manutenzione salvata - ID: " . $maintenanceData['id']);
        return $maintenanceData;
    }
    
    /**
     * Render del widget
     */
    private function renderWidget() {
        return '
            <div class="widget">
                <div class="widget-header">
                    <h4>Plugin Esempio Attivo</h4>
                </div>
                <div class="widget-body">
                    <p>Questo è un widget di esempio creato da un plugin.</p>
                    <p>I plugin possono estendere le funzionalità del sistema.</p>
                </div>
            </div>
        ';
    }
    
    /**
     * Metodo chiamato quando il plugin viene disattivato
     */
    public function deactivate() {
        // Cleanup operations
    }
    
    /**
     * Metodo chiamato quando il plugin viene disinstallato
     */
    public function uninstall() {
        // Remove plugin data from database
    }
}
?>