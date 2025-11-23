#!/bin/bash
set -e

echo "🚀 Iniciando script de despliegue de Laravel..."

# Configurar variables por defecto si no existen
DB_HOST=${DB_HOST:-laravel_db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-laravel}
DB_USERNAME=${DB_USERNAME:-laravel_user}
DB_PASSWORD=${DB_PASSWORD:-password}

echo "📊 Configuración de BD:"
echo "   Host: $DB_HOST"
echo "   Puerto: $DB_PORT"
echo "   Base de datos: $DB_DATABASE"
echo "   Usuario: $DB_USERNAME"

# Esperar a que MariaDB esté listo (máximo 90 segundos)
echo "⏳ Esperando a que MariaDB esté disponible en $DB_HOST:$DB_PORT..."
for i in {1..45}; do
    if nc -z $DB_HOST $DB_PORT; then
        echo "✅ MariaDB está disponible en el puerto"
        break
    fi
    echo "⏳ Intento $i/45 - Esperando a MariaDB..."
    sleep 2
    
    # Si llegamos al último intento, salir con error
    if [ $i -eq 45 ]; then
        echo "❌ Timeout: MariaDB no está disponible después de 90 segundos"
        exit 1
    fi
done

# Esperar un poco más para asegurar que MariaDB esté completamente inicializado
echo "⏳ Esperando inicialización completa de MariaDB..."
sleep 10

# Verificar si podemos conectar a la base de datos (múltiples intentos)
echo "🔍 Verificando conexión a la base de datos..."
for i in {1..10}; do
    if php -r "
    try {
        \$pdo = new PDO('mysql:host=$DB_HOST;port=$DB_PORT', '$DB_USERNAME', '$DB_PASSWORD');
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo '✅ Conexión a BD exitosa\n';
        exit(0);
    } catch (PDOException \$e) {
        echo '⏳ Intento $i/10 - Error conectando a BD: ' . \$e->getMessage() . '\n';
        exit(1);
    }
    "; then
        break
    fi
    sleep 3
    
    if [ $i -eq 10 ]; then
        echo "❌ No se pudo conectar a la base de datos después de 10 intentos"
        exit 1
    fi
done

# Verificar si la base de datos existe, si no crearla
echo "🗃️ Verificando base de datos..."
php -r "
try {
    \$pdo = new PDO('mysql:host=$DB_HOST;port=$DB_PORT', '$DB_USERNAME', '$DB_PASSWORD');
    \$pdo->exec('USE `$DB_DATABASE`');
    echo '✅ Base de datos existe\n';
} catch (PDOException \$e) {
    echo '📦 Creando base de datos...\n';
    \$pdo->exec('CREATE DATABASE IF NOT EXISTS `$DB_DATABASE` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    
    # Verificar que se creó correctamente
    \$pdo->exec('USE `$DB_DATABASE`');
    echo '✅ Base de datos creada exitosamente\n';
    
    # Otorgar permisos al usuario si es necesario
    \$pdo->exec(\"GRANT ALL PRIVILEGES ON `$DB_DATABASE`.* TO '$DB_USERNAME'@'%'\");
    echo '✅ Permisos otorgados al usuario\n';
}
" || {
    echo "❌ Error al verificar/crear la base de datos"
    exit 1
}

# Verificar si el archivo .env existe, si no crearlo desde .env.example
if [ ! -f ".env" ]; then
    echo "📄 Creando archivo .env desde .env.example..."
    cp .env.example .env
else
    echo "✅ Archivo .env existe"
fi

# Asegurar que las variables de BD estén en el .env
echo "🔧 Configurando variables de BD en .env..."
sed -i "s/^DB_HOST=.*/DB_HOST=$DB_HOST/" .env
sed -i "s/^DB_PORT=.*/DB_PORT=$DB_PORT/" .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env

# Instalar dependencias de Composer
echo "📦 Instalando dependencias de Composer..."
# Siempre instalar/actualizar para asegurar consistencia
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Generar key de Laravel si no existe
if [ -z "$(grep -E '^APP_KEY=.+$' .env)" ] || grep -q '^APP_KEY=$' .env || grep -q 'Your32CharacterKeyHere' .env; then
    echo "🔑 Generando key de Laravel..."
    php artisan key:generate --force
else
    echo "✅ APP_KEY ya configurada"
fi

# Ejecutar migraciones
echo "🗃️ Ejecutando migraciones..."
php artisan migrate --force

# Ejecutar seeders si existe la bandera o en entorno de desarrollo
if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo "🌱 Ejecutando seeders..."
    php artisan db:seed --force
else
    echo "⏩ Saltando seeders (RUN_SEEDERS no está habilitado)"
fi

# Limpiar cache antes de optimizar
echo "🧹 Limpiando cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimizar Laravel para producción
echo "⚡ Optimizando Laravel..."
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Crear enlace de storage si no existe
if [ ! -L "public/storage" ]; then
    echo "📁 Creando enlace de storage..."
    php artisan storage:link
fi

# Verificar la salud de la aplicación
echo "🏥 Verificando salud de la aplicación..."
php -r "
try {
    \$pdo = new PDO('mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD');
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar que podemos ejecutar una consulta simple
    \$stmt = \$pdo->query('SELECT 1');
    if (\$stmt->fetchColumn() === '1') {
        echo '✅ Salud de BD: OK\n';
    } else {
        throw new Exception('Consulta de salud falló');
    }
} catch (Exception \$e) {
    echo '❌ Error en salud de BD: ' . \$e->getMessage() . '\n';
    exit(1);
}

// Verificar que Laravel puede bootear
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
echo '✅ Salud de Laravel: OK\n';
"

echo ""
echo "🎉 ¡Despliegue completado exitosamente!"
echo "📊 Resumen:"
echo "   ✅ MariaDB conectado"
echo "   ✅ Base de datos verificada/creada"
echo "   ✅ Dependencias instaladas"
echo "   ✅ Variables de entorno configuradas"
echo "   ✅ Migraciones ejecutadas"
echo "   ✅ Aplicación optimizada"
echo ""
echo "🚀 La aplicación está lista para usar!"