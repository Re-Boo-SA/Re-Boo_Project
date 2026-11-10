# Re-Boo_Project

## Tienda de skins

La tienda usa `JUGADORES.PntsPartida` como saldo y guarda las compras en `jugador_skins`.
Si la base de datos ya existía antes de habilitar la tienda, ejecutá
[`sql/migracion_tienda_skins.sql`](./sql/migracion_tienda_skins.sql) sobre `proyecto2026`.
La migración crea las tablas de la tienda que falten y agrega las dos skins de ejemplo
del catálogo cuando todavía no existen.