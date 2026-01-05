// assets/js/qrcode.js

const QRCode = {
    // Generate QR code for asset
    generateAssetQR: function(assetId, assetCode) {
        const url = `${APP_CONFIG.baseUrl}/asset-detail.php?id=${assetId}`;
        const qrUrl = Utils.generateQRCode(url);
        
        return qrUrl;
    },
    
    // Show QR code in modal
    showQRModal: function(assetId, assetCode, assetName) {
        const qrUrl = this.generateAssetQR(assetId, assetCode);
        
        const modal = $(`
            <div class="modal-overlay" id="qrModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>QR Code - ${assetName}</h3>
                        <button class="modal-close" id="closeQRModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body" style="text-align: center;">
                        <img src="${qrUrl}" alt="QR Code" style="max-width: 100%; height: auto;">
                        <p style="margin-top: 16px; color: var(--color-text-light);">
                            Codice Asset: <strong>${assetCode}</strong>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline" id="downloadQR">
                            <i class="fas fa-download"></i> Scarica
                        </button>
                        <button class="btn btn-primary" id="printQR">
                            <i class="fas fa-print"></i> Stampa
                        </button>
                    </div>
                </div>
            </div>
        `).appendTo('body');
        
        setTimeout(() => modal.addClass('show'), 10);
        
        // Close modal
        modal.find('#closeQRModal, .modal-overlay').on('click', function(e) {
            if (e.target === e.currentTarget) {
                modal.removeClass('show');
                setTimeout(() => modal.remove(), 300);
            }
        });
        
        // Download QR code
        modal.find('#downloadQR').on('click', function() {
            const link = document.createElement('a');
            link.href = qrUrl;
            link.download = `qr-${assetCode}.png`;
            link.click();
        });
        
        // Print QR code
        modal.find('#printQR').on('click', function() {
            QRCode.printQR(qrUrl, assetCode, assetName);
        });
    },
    
    // Print QR code
    printQR: function(qrUrl, assetCode, assetName) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>QR Code - ${assetCode}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        margin: 0;
                        padding: 20px;
                    }
                    .qr-container {
                        text-align: center;
                        border: 2px solid #333;
                        padding: 30px;
                        border-radius: 10px;
                    }
                    img {
                        max-width: 300px;
                        height: auto;
                    }
                    h2 {
                        margin: 20px 0 10px;
                        color: #333;
                    }
                    p {
                        margin: 5px 0;
                        color: #666;
                    }
                    .code {
                        font-size: 1.5rem;
                        font-weight: bold;
                        margin-top: 15px;
                        color: #000;
                    }
                    @media print {
                        body {
                            print-color-adjust: exact;
                            -webkit-print-color-adjust: exact;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="qr-container">
                    <img src="${qrUrl}" alt="QR Code">
                    <h2>${assetName}</h2>
                    <p class="code">${assetCode}</p>
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        window.onafterprint = function() {
                            window.close();
                        };
                    };
                </script>
            </body>
            </html>
        `);
        printWindow.document.close();
    },
    
    // Scan QR code (using device camera)
    scanQR: function(onSuccess) {
        const modal = $(`
            <div class="modal-overlay" id="qrScanModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>Scansiona QR Code</h3>
                        <button class="modal-close" id="closeScanModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="qrReader" style="width: 100%;"></div>
                        <p style="text-align: center; margin-top: 16px; color: var(--color-text-light);">
                            Inquadra il QR code dell'asset
                        </p>
                    </div>
                </div>
            </div>
        `).appendTo('body');
        
        setTimeout(() => modal.addClass('show'), 10);
        
        // Note: QR Scanner requires html5-qrcode library
        // For production, include: <script src="https://unpkg.com/html5-qrcode"></script>
        Utils.showToast('Scanner QR in sviluppo - usa manualmente il codice', 'info');
        
        // Close modal
        modal.find('#closeScanModal, .modal-overlay').on('click', function(e) {
            if (e.target === e.currentTarget) {
                modal.removeClass('show');
                setTimeout(() => modal.remove(), 300);
            }
        });
    }
};

// Initialize QR code buttons
$(document).ready(function() {
    // Show QR code button
    $(document).on('click', '[data-show-qr]', function() {
        const assetId = $(this).data('asset-id');
        const assetCode = $(this).data('asset-code');
        const assetName = $(this).data('asset-name');
        
        QRCode.showQRModal(assetId, assetCode, assetName);
    });
    
    // Scan QR code button
    $(document).on('click', '[data-scan-qr]', function() {
        QRCode.scanQR(function(result) {
            console.log('QR Scanned:', result);
        });
    });
});