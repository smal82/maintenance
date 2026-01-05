<?php
set_time_limit(300);
ini_set('memory_limit', '256M');

$report = [
    'processed' => [],
    'skipped'   => [],
    'errors'    => []
];

$is_submitted = isset($_POST['esegui_tutto']);

if ($is_submitted) {
    $base = realpath('.');
    
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (!$file->isFile()) continue;
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['php', 'css', 'js'])) continue;
            if ($file->getRealPath() === __FILE__) continue;

            $path = $file->getRealPath();
            $rel = str_replace($base . DIRECTORY_SEPARATOR, '', $path);
            $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
            
            $content = file_get_contents($path);
            $new_content = $content;
            $modified = false;

            if ($ext === 'php') {
                $comment_string = "// " . $rel;
                
                // Pattern per verificare se il commento è già dentro un tag PHP all'inizio
                $pattern_check = '/^<\?php\s*' . preg_quote($comment_string, '/') . '/i';
                
                // 1. Se è già perfettamente posizionato, saltiamo
                if (preg_match($pattern_check, ltrim($content))) {
                    $report['skipped'][] = $rel;
                    continue;
                }

                // 2. Pulizia: rimuoviamo il commento se esiste altrove o fuori dai tag
                $search_bad = [
                    $comment_string . "\n",
                    $comment_string . "\r\n",
                    "<?php\n" . $comment_string . "\n?>\n",
                    "<?php " . $comment_string . " ?>\n",
                    "<?php\n" . $comment_string . "\n?>",
                ];
                $new_content = str_replace($search_bad, '', $content);
                
                // 3. Inserimento corretto
                $trimmed_content = ltrim($new_content);
                if (stripos($trimmed_content, '<?php') === 0) {
                    // C'è già il tag PHP, inseriamo subito dopo l'apertura
                    $new_content = preg_replace('/^<\?php\s*/i', "<?php\n" . $comment_string . "\n", $trimmed_content, 1);
                } else {
                    // Non c'è il tag PHP o è un file HTML/PHP misto senza tag iniziale
                    $new_content = "<?php\n" . $comment_string . "\n?>\n" . $trimmed_content;
                }
                $modified = true;

            } else {
                // Gestione CSS e JS
                $comment_style = ($ext === 'css') ? "/* " . $rel . " */" : "// " . $rel;
                if (strpos(ltrim($content), $comment_style) === 0) {
                    $report['skipped'][] = $rel;
                    continue;
                }
                $new_content = $comment_style . "\n" . ltrim($content);
                $modified = true;
            }

            if ($modified) {
                if (file_put_contents($path, $new_content) !== false) {
                    $report['processed'][] = $rel;
                } else {
                    $report['errors'][] = $rel;
                }
            }
        }
    } catch (Exception $e) {
        $global_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Sincronizzazione Percorsi File</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); padding: 40px; }
        h1 { text-align: center; color: #4a5568; margin-bottom: 20px; }
        .btn-container { text-align: center; margin: 30px 0; }
        .btn { background: #764ba2; color: white; border: none; padding: 15px 40px; font-size: 18px; font-weight: bold; border-radius: 50px; cursor: pointer; transition: 0.3s; }
        .btn:hover { background: #667eea; transform: translateY(-2px); }
        .report-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { padding: 20px; border-radius: 15px; text-align: center; border: 1px solid #eee; }
        .processed { background: #e6fffa; color: #2c7a7b; }
        .skipped { background: #f7fafc; color: #4a5568; }
        .errors { background: #fff5f5; color: #c53030; }
        .stat-num { font-size: 30px; font-weight: bold; display: block; }
        .log-area { max-height: 450px; overflow-y: auto; background: #2d3748; padding: 20px; border-radius: 10px; font-family: 'Courier New', monospace; font-size: 13px; color: #edf2f7; }
        .log-entry { margin-bottom: 4px; padding: 4px; border-radius: 3px; }
        .log-p { border-left: 4px solid #38b2ac; background: rgba(56, 178, 172, 0.1); }
        .log-s { border-left: 4px solid #718096; color: #a0aec0; }
        .log-e { border-left: 4px solid #f56565; background: rgba(245, 101, 101, 0.1); }
        h3 { margin-bottom: 15px; color: #a0aec0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🚀 Correzione Percorsi</h1>
    
    <?php if (isset($global_error)): ?>
        <div style="background: #fff5f5; color: #c53030; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <strong>Errore di sistema:</strong> <?php echo $global_error; ?>
        </div>
    <?php endif; ?>

    <?php if (!$is_submitted): ?>
        <p style="text-align: center; color: #718096; margin-bottom: 20px;">
            Questo script analizzerà i file e sistemerà i commenti PHP spostandoli correttamente dentro i tag di apertura.
        </p>
        <form method="post" class="btn-container">
            <button type="submit" name="esegui_tutto" class="btn">AVVIA PROCESSO</button>
        </form>
    <?php else: ?>
        <div class="report-grid">
            <div class="stat-card processed">
                <span class="stat-num"><?php echo count($report['processed']); ?></span>
                MODIFICATI
            </div>
            <div class="stat-card skipped">
                <span class="stat-num"><?php echo count($report['skipped']); ?></span>
                INVARIATI
            </div>
            <div class="stat-card errors">
                <span class="stat-num"><?php echo count($report['errors']); ?></span>
                ERRORI
            </div>
        </div>

        <div class="log-area">
            <h3>Dettaglio File:</h3>
            <?php foreach ($report['processed'] as $f): ?>
                <div class="log-entry log-p">🔧 SISTEMATO: <?php echo $f; ?></div>
            <?php endforeach; ?>
            <?php foreach ($report['skipped'] as $f): ?>
                <div class="log-entry log-s">⏭️ OK (Già corretto): <?php echo $f; ?></div>
            <?php endforeach; ?>
            <?php foreach ($report['errors'] as $f): ?>
                <div class="log-entry log-e">❌ ERRORE: <?php echo $f; ?></div>
            <?php endforeach; ?>
        </div>

        <div class="btn-container">
            <a href="?" class="btn" style="text-decoration: none; background: #4a5568;">🔄 NUOVA SCANSIONE</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>