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
# SECCIÓN CRÍTICA: VERIFICAR VENDOR ANTES DE INSTALAR
# ============================================================================

echo "📦 Verificando estado de dependencias..."

# Función para verificar si vendor está completo
check_vendor() {
    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ] && [ -d "vendor/composer" ]; then
        echo "✅ Vendor está completo"
        return 0
    else
        echo "❌ Vendor está incompleto o corrupto"
        return 1
    fi
}

# Solo instalar si vendor NO está completo
if ! check_vendor; then
    echo "🔧 Reinstalando dependencias de Composer..."
    
    # Limpiar si existe pero está corrupto
    if [ -d "vendor" ]; then
        echo "🧹 Limpiando vendor corrupto..."
        rm -rf vendor
    fi
    
    # Instalar dependencias
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
    
    # Verificar que se instaló correctamente
    if ! check_vendor; then
        echo "💥 Error crítico: No se pudieron instalar las dependencias"
        exit 1
    fi
    echo "✅ Dependencias instaladas correctamente"
else
    echo "✅ Dependencias ya están instaladas"
fi

# Regenerar autoloader (siempre seguro)
echo "🔄 Regenerando autoloader..."
composer dump-autoload --optimize --no-dev

# ============================================================================
# SECCIÓN DE BASE DE DATOS
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
    \$pdo->exec('USE \`$DB_DATABASE\`');
    echo '✅ Base de datos creada exitosamente\n';
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

# Seeders opcionales
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

echo ""
echo "🎉 ¡Despliegue completado exitosamente!"
echo "📊 Resumen:"
echo "   ✅ Dependencias verificadas"
echo "   ✅ MariaDB conectado" 
echo "   ✅ Base de datos configurada"
echo "   ✅ Migraciones ejecutadas"
echo "   ✅ Aplicación optimizada"
echo ""
echo "🚀 La aplicación está lista para usar!"