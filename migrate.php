<?php
require_once 'includes/config.php';

// Diretório onde estão os arquivos de migrations
$migrationsDir = __DIR__ . '/migrations';

// Busca todos os arquivos .sql no diretório
$migrationFiles = glob($migrationsDir . '/*.sql');

// Add the new migration file
$migrationFiles = glob($migrationsDir . '/*.sql');

// Ordena os arquivos (assumindo que os arquivos estão numerados para indicar a ordem)
sort($migrationFiles);

echo "Iniciando as migrations...\n\n";

foreach ($migrationFiles as $file) {
    echo "Executando " . basename($file) . "...\n";
    $sql = file_get_contents($file);
    try {
        // Executa o SQL contido no arquivo
        $pdo->exec($sql);
        echo "Sucesso!\n\n";
    } catch (PDOException $e) {
        echo "Erro na migration " . basename($file) . ": " . $e->getMessage() . "\n\n";
        // Opcional: interromper a execução em caso de erro
        // exit;
    }
}

echo "Todas as migrations foram executadas.\n";
?>
