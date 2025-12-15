<?php
/**
 * Chargeur de variables d'environnement
 * Charge les variables depuis le fichier .env
 */

class EnvLoader {
    /**
     * Charger les variables d'environnement depuis le fichier .env
     */
    public static function load($path = null) {
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }

        // Si le fichier .env n'existe pas, ne rien faire (utiliser les valeurs par défaut)
        if (!file_exists($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parser la ligne (format: KEY=VALUE)
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Supprimer les guillemets si présents
                $value = trim($value, '"\'');

                // Définir la variable d'environnement
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }

        return true;
    }

    /**
     * Obtenir une variable d'environnement avec une valeur par défaut
     */
    public static function get($key, $default = null) {
        // Chercher dans $_ENV
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // Chercher avec getenv()
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        // Retourner la valeur par défaut
        return $default;
    }

    /**
     * Vérifier si une variable d'environnement existe
     */
    public static function has($key) {
        return isset($_ENV[$key]) || getenv($key) !== false;
    }
}

// Charger automatiquement le fichier .env lors de l'inclusion
EnvLoader::load();
?>
