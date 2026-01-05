<?php
// asset-map.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();

// Get all assets (in future you can add lat/lng columns to assets table)
$assets = $db->fetchAll("
    SELECT a.*, ac.name as category_name, ac.color as category_color
    FROM assets a
    LEFT JOIN asset_categories ac ON a.category_id = ac.id
    WHERE a.status != 'retired'
    ORDER BY a.name
");

$pageTitle = 'Mappa Asset';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Mappa Asset</h3>
            <div style="display: flex; gap: 12px;">
                <select id="categoryFilter" class="form-control" style="width: 200px;">
                    <option value="">Tutte le categorie</option>
                    <?php
                    $categories = $db->fetchAll("SELECT * FROM asset_categories ORDER BY name");
                    foreach ($categories as $cat):
                    ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="statusFilter" class="form-control" style="width: 180px;">
                    <option value="">Tutti gli stati</option>
                    <option value="operational">Operativo</option>
                    <option value="maintenance">Manutenzione</option>
                    <option value="broken">Guasto</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <!-- Leaflet Map -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <div id="map" style="width: 100%; height: 600px; border-radius: 8px;"></div>
            
            <!-- Asset List -->
            <div style="margin-top: 32px;">
                <h4 style="margin-bottom: 16px;">Elenco Asset</h4>
                <div class="form-grid form-grid-3" id="assetList">
                    <?php foreach ($assets as $asset): ?>
                    <div class="card asset-card" 
                         data-id="<?php echo $asset['id']; ?>"
                         data-category="<?php echo $asset['category_id'] ?? ''; ?>" 
                         data-status="<?php echo $asset['status']; ?>"
                         style="cursor: pointer;">
                        <div class="card-body" style="padding: 16px;">
                            <div style="display: flex; align-items: start; gap: 12px;">
                                <div style="flex: 1;">
                                    <h5 style="margin-bottom: 8px;">
                                        <?php echo htmlspecialchars($asset['name']); ?>
                                    </h5>
                                    <p style="color: var(--color-text-light); font-size: 0.875rem; margin-bottom: 8px;">
                                        <strong><?php echo htmlspecialchars($asset['code']); ?></strong>
                                    </p>
                                    <?php if ($asset['category_name']): ?>
                                    <span class="badge" style="background-color: <?php echo $asset['category_color'] ?? '#3498db'; ?>20; color: <?php echo $asset['category_color'] ?? '#3498db'; ?>; border: 1px solid <?php echo $asset['category_color'] ?? '#3498db'; ?>; margin-bottom: 8px;">
                                        <?php echo htmlspecialchars($asset['category_name']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <p style="color: var(--color-text-light); font-size: 0.875rem; margin-bottom: 4px;">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($asset['location'] ?? 'N/D'); ?>
                                    </p>
                                    <div class="table-cell-status" style="margin-top: 8px;">
                                        <span class="status-dot <?php echo $asset['status']; ?>"></span>
                                        <?php
                                        $statusLabels = [
                                            'operational' => 'Operativo',
                                            'maintenance' => 'Manutenzione',
                                            'broken' => 'Guasto',
                                            'retired' => 'Dismesso'
                                        ];
                                        echo $statusLabels[$asset['status']] ?? $asset['status'];
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 12px; display: flex; gap: 8px;">
                                <a href="<?php echo BASE_URL; ?>/asset-form.php?id=<?php echo $asset['id']; ?>" class="btn btn-sm btn-outline" style="flex: 1;">
                                    <i class="fas fa-edit"></i> Modifica
                                </a>
                                <button class="btn btn-sm btn-primary" 
                                        data-show-qr
                                        data-asset-id="<?php echo $asset['id']; ?>"
                                        data-asset-code="<?php echo htmlspecialchars($asset['code']); ?>"
                                        data-asset-name="<?php echo htmlspecialchars($asset['name']); ?>"
                                        style="flex: 1;">
                                    <i class="fas fa-qrcode"></i> QR
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(document).ready(function() {
    // Inizializza mappa centrata su Palermo
    const map = L.map('map').setView([38.1157, 13.3615], 13);
    
    // Aggiungi tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Genera posizioni casuali per gli asset nell'area di Palermo
    const assets = <?php echo json_encode($assets); ?>;
    const center = {lat: 38.1157, lng: 13.3615};
    const markers = {};
    
    assets.forEach((asset, index) => {
        // Genera coordinate casuali in un raggio di ~2km dal centro
        const lat = center.lat + (Math.random() - 0.5) * 0.03;
        const lng = center.lng + (Math.random() - 0.5) * 0.03;
        
        // Colore marker in base allo stato
        const colors = {
            operational: 'green',
            maintenance: 'orange',
            broken: 'red',
            retired: 'gray'
        };
        
        const color = colors[asset.status] || 'blue';
        
        // Crea icona personalizzata
        const icon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background-color: ${color}; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">${index + 1}</div>`,
            iconSize: [30, 30]
        });
        
        // Aggiungi marker
        const marker = L.marker([lat, lng], {icon: icon}).addTo(map);
        
        // Popup
        marker.bindPopup(`
            <div style="min-width: 200px;">
                <h5 style="margin-bottom: 8px;">${asset.name}</h5>
                <p style="margin: 4px 0;"><strong>Codice:</strong> ${asset.code}</p>
                <p style="margin: 4px 0;"><strong>Categoria:</strong> ${asset.category_name || 'N/D'}</p>
                <p style="margin: 4px 0;"><strong>Posizione:</strong> ${asset.location || 'N/D'}</p>
                <p style="margin: 4px 0;"><strong>Stato:</strong> <span style="color: ${color};">●</span> ${asset.status}</p>
                <a href="<?php echo BASE_URL; ?>/asset-form.php?id=${asset.id}" class="btn btn-sm btn-primary" style="margin-top: 8px; width: 100%;">Modifica</a>
            </div>
        `);
        
        markers[asset.id] = marker;
    });
    
    // Filtri
    $('#categoryFilter, #statusFilter').on('change', function() {
        filterAssets();
    });
    
    // Click su asset card per centrare mappa
    $(document).on('click', '.asset-card', function() {
        const assetId = $(this).data('id');
        const marker = markers[assetId];
        if (marker) {
            map.setView(marker.getLatLng(), 16);
            marker.openPopup();
        }
    });
});

function filterAssets() {
    const category = $('#categoryFilter').val();
    const status = $('#statusFilter').val();
    
    $('.asset-card').each(function() {
        const cardCategory = $(this).data('category');
        const cardStatus = $(this).data('status');
        
        let show = true;
        
        if (category && cardCategory != category) {
            show = false;
        }
        
        if (status && cardStatus !== status) {
            show = false;
        }
        
        $(this).toggle(show);
    });
}
</script>

<style>
.asset-card {
    transition: all 0.3s ease;
}

.asset-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}
</style>

<?php include 'includes/footer.php'; ?>