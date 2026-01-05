<?php
// calendar.php
require_once 'config.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = 'Calendario Manutenzioni';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3>Calendario Manutenzioni</h3>
            <?php if ($auth->hasPermission('create_maintenance') || $auth->hasRole('admin')): ?>
            <a href="<?php echo BASE_URL; ?>/maintenance-form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nuova Manutenzione
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div id="maintenanceCalendar"></div>
            
            <!-- Events list for selected date -->
            <div id="selectedDateEvents" style="margin-top: 32px; display: none;">
                <h4 id="selectedDateTitle" style="margin-bottom: 16px;"></h4>
                <div id="eventsList"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    Calendar.init('maintenanceCalendar');
    
    // Override date click to show events
    Calendar.onDateClick = function(date) {
        showEventsForDate(date);
    };
    
    // Override month change to reload events
    Calendar.onMonthChange = function() {
        const year = Calendar.currentDate.getFullYear();
        const month = Calendar.currentDate.getMonth() + 1;
        Calendar.loadEvents(year, month);
    };
    
    // Load initial events
    const now = new Date();
    Calendar.loadEvents(now.getFullYear(), now.getMonth() + 1);
});

function showEventsForDate(date) {
    const events = Calendar.events.filter(e => e.date === date);
    
    if (events.length === 0) {
        $('#selectedDateEvents').hide();
        return;
    }
    
    const dateObj = new Date(date);
    const formattedDate = dateObj.toLocaleDateString('it-IT', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    $('#selectedDateTitle').text('Manutenzioni per ' + formattedDate);
    
    let html = '';
    events.forEach(event => {
        const priorityColors = {
            low: '#95a5a6',
            medium: '#f39c12',
            high: '#e74c3c',
            critical: '#c0392b'
        };
        
        html += `
            <div class="card" style="margin-bottom: 16px; border-left: 4px solid ${event.color};">
                <div class="card-body" style="padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h5 style="margin-bottom: 8px;">
                                ${event.title}
                                <span class="badge badge-${event.priority}" style="margin-left: 8px;">${event.priority}</span>
                                <span class="badge badge-${event.status}" style="margin-left: 4px;">${event.status}</span>
                            </h5>
                            <p style="color: var(--color-text-light); margin-bottom: 8px;">
                                <i class="fas fa-box"></i> ${event.asset_code} - ${event.asset_name}
                            </p>
                            <p style="color: var(--color-text-light);">
                                <i class="fas fa-clock"></i> ${event.time}
                            </p>
                        </div>
                        <div>
                            <a href="<?php echo BASE_URL; ?>/maintenance-form.php?id=${event.id}" class="btn btn-sm btn-outline">
                                <i class="fas fa-edit"></i> Modifica
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#eventsList').html(html);
    $('#selectedDateEvents').show();
}
</script>

<?php include 'includes/footer.php'; ?>