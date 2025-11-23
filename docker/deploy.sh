#!/bin/bash
set -e

echo "🚀 Iniciando script de despliegue de Laravel..."

# Configurar variables por defecto si no existen
DB_HOST=${DB_HOST:-laravel_db}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-iyata}
DB_USERNAME=${DB_USERNAME:-laravel}
DB_PASSWORD=${DB_PASSWORD:-password}

echo "📊 Configuración de BD:"
echo "   Host: $DB_HOST"
echo "   Puerto: $DB_PORT"
echo "   Base de datos: $DB_DATABASE"
echo "   Usuario: $DB_USERNAME"

# ============================================================================
# VERIFICACIÓN E INSTALACIÓN DE DEPENDENCIAS
# ============================================================================

echo "📦 Verificando dependencias de Composer..."

# Función para verificar integridad de vendor
check_vendor_integrity() {
    if [ ! -f "vendor/autoload.php" ] || [ ! -d "vendor" ]; then
        echo "❌ vendor/autoload.php no existe o vendor/ está corrupto"
        return 1
    fi
    
    # Verificar que composer.json y vendor estén sincronizados
    if ! composer validate --no-check-all --quiet 2>/dev/null; then
        echo "❌ Validación de Composer falló"
        return 1
    fi
    
    # Verificar que las dependencias principales existan
    if [ ! -d "vendor/laravel" ] || [ ! -d "vendor/illuminate" ]; then
        echo "❌ Dependencias principales faltantes"
        return 1
    fi
    
    echo "✅ Integridad de dependencias verificada"
    return 0
}

# Verificar si necesitamos instalar/reinstalar dependencias
if ! check_vendor_integrity; then
    echo "🔧 Instalando/Reinstalando dependencias de Composer..."
    
    # Limpiar vendor si existe pero está corrupto
    if [ -d "vendor" ]; then
        echo "🧹 Limpiando vendor corrupto..."
        rm -rf vendor/*
    fi
    
    # Instalar dependencias
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
    
    # Verificar que la instalación fue exitosa
    if ! check_vendor_integrity; then
        echo "❌ Error crítico: No se pudieron instalar las dependencias"
        exit 1
    fi
else
    echo "✅ Dependencias ya instaladas y validadas"
    
    # Actualizar autoloader por si acaso
    composer dump-autoload --optimize --no-dev
fi

# ============================================================================
# SECCIÓN ORIGINAL: CONEXIÓN A BASE DE DATOS
# ============================================================================

# Esperar a que MariaDB esté listo (máximo 90 segundos)
echo "⏳ Esperando a que MariaDB esté disponible en $DB_HOST:$DB_PORT..."
for i in {1..45}; do
    if nc -z $DB_HOST $DB_PORT; then
        echo "✅ MariaDB está disponible en el puerto"
        break
    fi
    echo "⏳ Intento $i/45 - Esperando a MariaDB..."
    sleep 2
    
    if [ $i -eq 45 ]; then
        echo "❌ Timeout: MariaDB no está disponible después de 90 segundos"
        exit 1
    fi
done

echo "⏳ Esperando inicialización completa de MariaDB..."
sleep 10

# Verificar conexión a la base de datos
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

# Verificar/crear base de datos
echo "🗃️ Verificando base de datos..."
php -r "
try {
    \$pdo = new PDO('mysql:host=$DB_HOST;port=$DB_PORT', '$DB_USERNAME', '$DB_PASSWORD');
    \$pdo->exec('USE \`$DB_DATABASE\`');
    echo '✅ Base de datos existe\n';
} catch (PDOException \$e) {
    echo '📦 Creando base de datos...\n';
    \$pdo->exec('CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    
    # Verificar que se creó correctamente
    \$pdo->exec('USE \`$DB_DATABASE\`');
    echo '✅ Base de datos creada exitosamente\n';
    
    # Otorgar permisos al usuario si es necesario
    \$pdo->exec(\"GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'%'\");
    echo '✅ Permisos otorgados al usuario\n';
}
" || {
    echo "❌ Error al verificar/crear la base de datos"
    exit 1
}

# ============================================================================
# SECCIÓN DE CONFIGURACIÓN LARAVEL
# ============================================================================

# Configurar .env
if [ ! -f ".env" ]; then
    echo "📄 Creando archivo .env desde .env.example..."
    cp .env.example .env
else
    echo "✅ Archivo .env existe"
fi

# Configurar variables de BD en .env
echo "🔧 Configurando variables de BD en .env..."
sed -i "s/^DB_HOST=.*/DB_HOST=$DB_HOST/" .env
sed -i "s/^DB_PORT=.*/DB_PORT=$DB_PORT/" .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env

# ============================================================================
# VERIFICAR DEPENDENCIAS NUEVAMENTE ANTES DE MIGRAR
# ============================================================================

echo "🔍 Verificación final de dependencias antes de migrar..."
if ! check_vendor_integrity; then
    echo "❌ Error crítico: Dependencias corruptas antes de migrar"
    echo "🔄 Reinstalando dependencias de emergencia..."
    rm -rf vendor
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
    
    if ! check_vendor_integrity; then
        echo "💥 Error fatal: No se pudieron recuperar las dependencias"
        exit 1
    fi
fi

# Generar key de Laravel si no existe
if [ -z "\$(grep -E '^APP_KEY=.+\$' .env)" ] || grep -q '^APP_KEY=\$' .env || grep -q 'Your32CharacterKeyHere' .env; then
    echo "🔑 Generando key de Laravel..."
    php artisan key:generate --force
else
    echo "✅ APP_KEY ya configurada"
fi

# Ejecutar migraciones
echo "🗃️ Ejecutando migraciones..."
php artisan migrate --force

# Ejecutar seeders si existe la bandera o en entorno de desarrollo
if [ "\${RUN_SEEDERS:-false}" = "true" ]; then
    echo "🌱 Ejecutando seeders..."
    php artisan db:seed --force
else
    echo "⏩ Saltando seeders (RUN_SEEDERS no está habilitado)"
fi

# Optimizar Laravel
echo "⚡ Optimizando Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Storage link
if [ ! -L "public/storage" ]; then
    echo "📁 Creando enlace de storage..."
    php artisan storage:link
fi

# Verificar la salud de la aplicación
echo "🏥 Verificando salud de la aplicación..."
php -r "
try {
    \$pdo = new PDO('mysql:host=\$DB_HOST;port=\$DB_PORT;dbname=\$DB_DATABASE', '\$DB_USERNAME', '\$DB_PASSWORD');
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

# ============================================================================
# VERIFICACIÓN FINAL
# ============================================================================

echo "🔍 Verificación final del despliegue..."

# Verificar que artisan funcione
if php artisan --version > /dev/null 2>&1; then
    echo "✅ Artisan funcionando correctamente"
else
    echo "❌ Error: Artisan no funciona"
    exit 1
fi

# Verificar que las rutas estén cargadas
if php artisan route:list --no-ansi > /dev/null 2>&1; then
    echo "✅ Rutas cargadas correctamente"
else
    echo "❌ Error: No se pueden cargar las rutas"
    exit 1
fi

echo ""
echo "🎉 ¡Despliegue completado exitosamente!"
echo "📊 Resumen:"
echo "   ✅ Dependencias verificadas e instaladas"
echo "   ✅ MariaDB conectado"
echo "   ✅ Base de datos verificada/creada"
echo "   ✅ Variables de entorno configuradas"
echo "   ✅ Migraciones ejecutadas"
echo "   ✅ Aplicación optimizada"
echo "   ✅ Salud de la aplicación verificada"
echo ""
echo "🚀 La aplicación está lista para usar!"