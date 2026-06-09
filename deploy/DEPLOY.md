# Deploy a producción — módulo Eventualidades

Runbook ordenado. Producción es **otra base** que la de prueba; tené a mano host/usuario/clave de prod.
PHP de la app: **7.0** (Laravel 5.4). El `php` 8.x del sistema rompe artisan — para correr scripts usar `php7.0`.

Archivos de esta carpeta:
- `01_esquema_eventualidades.sql` — tablas + lookup + seed + permisos (idempotente).
- `02_migrar_procedimientos_viejos.php` — migra las eventualidades viejas (dry-run / commit).

---

## 0. Antes de empezar
- [ ] Avisar / ventana de mantenimiento (opcional pero recomendado para un cutover limpio).
- [ ] **Backup de la base de prod** (sí o sí):
  ```bash
  mysqldump -h <HOST_PROD> -u <USER> -p <BASE_PROD> --single-transaction --default-character-set=utf8mb4 > backup_pre_eventualidades_$(date +%F).sql
  ```
- [ ] Backup extra de la columna vieja, por las dudas (la usamos para migrar):
  ```bash
  mysqldump -h <HOST_PROD> -u <USER> -p <BASE_PROD> eventualidades > backup_eventualidades_$(date +%F).sql
  ```

## 1. Esquema + seed + permisos
- [ ] Correr el SQL:
  ```bash
  mysql -h <HOST_PROD> -u <USER> -p <BASE_PROD> --default-character-set=utf8mb4 < 01_esquema_eventualidades.sql
  ```
- [ ] Verificar:
  ```sql
  SELECT COUNT(*) FROM procedimiento;                                -- 14
  SELECT COUNT(*) FROM casino_tiene_procedimiento;                   -- 14 * (cantidad de casinos)
  SELECT * FROM estado_eventualidad ORDER BY id_estado_eventualidad; -- tiene la fila 0 = SIN TERMINAR
  SELECT descripcion FROM permiso WHERE descripcion IN ('abm_procedimientos','visar_resumen_diario'); -- las 2
  ```

## 2. Migrar las eventualidades viejas (JSON → tabla puente)
> Pasa los procedimientos de las ~500 viejas a la tabla nueva, matcheando por nombre.
- [ ] Editar `02_migrar_procedimientos_viejos.php` y completar `CAMBIAR_*_PROD` con las credenciales de prod.
- [ ] **Dry-run** (no escribe):
  ```bash
  php7.0 02_migrar_procedimientos_viejos.php
  ```
  - Esperado: "Eventualidades a migrar: ~500", "salteadas: 0", y **"✅ Todos los procedimientos matchean"**.
  - ⚠ Si aparece algún **"NOMBRE SIN MATCH"**, **PARAR**: ese nombre del JSON viejo no está en el catálogo.
    Agregalo al seed (`01_*.sql`), volvé a correr el SQL, y repetí el dry-run hasta que dé 0 sin-match.
- [ ] **Commit** (escribe):
  ```bash
  php7.0 02_migrar_procedimientos_viejos.php --commit
  ```
- [ ] Verificar (la puente debería tener ≈ tantas eventualidades como JSON viejo no vacío):
  ```sql
  SELECT COUNT(DISTINCT id_eventualidades) FROM eventualidad_tiene_procedimiento;
  SELECT COUNT(*) FROM eventualidades WHERE procedimientos IS NOT NULL AND procedimientos NOT IN ('','[]','null');
  ```

## 3. Deploy del código
- [ ] Subir la rama a prod (merge a master / pull / rsync) — incluye, entre otros:
  - `app/Http/Controllers/Eventualidades/` (EventualidadesController, ResumenDiarioController)
  - `app/Http/Controllers/ProcedimientoController.php`
  - `app/Procedimiento.php`, `app/Eventualidades/*` (modelos), `app/Casino.php`
  - `public/js/eventualidades/*` , `resources/views/Eventualidades/*` (+ `partials/`)
  - `routes/web.php`, `resources/views/includes/dashboard.blade.php`, `resources/views/seccionInicio.blade.php`
- [ ] Limpiar la caché de vistas compiladas (artisan está roto en esta app, así que a mano):
  ```bash
  rm -f storage/framework/views/*.php
  ```
- [ ] Permisos de escritura en `storage/app/public/` (las carpetas de archivos se crean solas al subir;
      sólo asegurate de que el usuario web pueda escribir ahí).

## 4. Smoke test (en prod, con un usuario admin)
- [ ] Abrir **una eventualidad vieja** → su PDF muestra los procedimientos (la migración funcionó).
- [ ] Cargar **una eventualidad nueva** → se guarda y el PDF sale bien.
- [ ] **Resumen diario** (menú Eventualidades → Resumen diario) → carga el reporte, visar/desvisar anda.
- [ ] **Gestionar procedimientos** (ABM) → alta/edición, activo por casino, borrado.
- [ ] Subir una **firmada** (PDF) → la detecta y pasa a firmada.
- [ ] Una **observación** con archivo → muestra fecha + foto del autor; CSV se descarga.
- [ ] Verificar el menú con un usuario **fiscalizador** (rol 3): ve "Eventualidades", NO ve "Resumen diario".

## 5. Post-deploy
- [ ] **NO** dropear la columna vieja `eventualidades.procedimientos` por ahora — dejarla como backup.
      Más adelante, con todo estable y backup confirmado:
      `ALTER TABLE eventualidades DROP COLUMN procedimientos;`
- [ ] Cerrar la ventana de mantenimiento.

---

## Rollback rápido
- Restaurar el backup de la base (`backup_pre_eventualidades_*.sql`) y volver el código a la versión anterior.
- Las tablas nuevas y la columna vieja conviven sin pisarse, así que un rollback de código solo (sin tocar
  la base) también deja el sistema viejo funcionando, porque la columna `procedimientos` sigue intacta.
