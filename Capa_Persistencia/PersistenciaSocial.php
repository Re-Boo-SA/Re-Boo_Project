<?php
require_once('IPersistenciaSocial.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/* Amigos, bloqueos, grupos, invitaciones y presencia (Fase 4). */
class PersistenciaSocial implements IPersistenciaSocial
{
    private ?PDO $conn = null;
    private static ?PersistenciaSocial $instancia = null;

    public static function getInstancia(): PersistenciaSocial
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception("No se puede deserializar el singletonSocial");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
        } catch (Exception $e) {
            error_log('Error de conexión en PersistenciaSocial: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('Lo social no está disponible por un problema de conexión.');
        }
        return $this->conn;
    }

    private function filas(string $sql, array $p = []): array
    {
        $s = $this->db()->prepare($sql);
        $s->execute($p);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fila(string $sql, array $p = []): ?array
    {
        $f = $this->filas($sql, $p);
        return $f[0] ?? null;
    }

    private function ejecutar(string $sql, array $p = []): int
    {
        $s = $this->db()->prepare($sql);
        $s->execute($p);
        return $s->rowCount();
    }

    /** "?,?,?" para una lista de enteros (vacía -> NULL, que nunca coincide). */
    private static function lista(array $ids): string
    {
        return $ids === [] ? 'NULL' : implode(',', array_map('intval', $ids));
    }

    public function iniciarTransaccion(): void { $this->db()->beginTransaction(); }
    public function confirmar(): void { if ($this->db()->inTransaction()) { $this->db()->commit(); } }
    public function revertir(): void { if ($this->db()->inTransaction()) { $this->db()->rollBack(); } }

    /* ---------- jugadores ---------- */

    private const CAMPOS_JUGADOR = 'u.IDUsuario, u.NombreUsuario, u.Rol, j.EsBot, j.MMR, j.XP, j.PartidasJugadas, j.PartidasGanadas,
        j.PuntosHistoricos, j.Bando, COALESCE(a.Piel * 12 + a.Pelo * 3 + a.Gorro, 0) AS Apariencia, j.Biografia';
    private const DESDE_JUGADOR = 'FROM USUARIOS u INNER JOIN JUGADORES j ON j.IDUsuario = u.IDUsuario
        LEFT JOIN APARIENCIA_JUGADOR a ON a.IDUsuario = u.IDUsuario';

    public function jugador(int $idUsuario): ?array
    {
        return $this->fila('SELECT ' . self::CAMPOS_JUGADOR . ' ' . self::DESDE_JUGADOR . ' WHERE u.IDUsuario = :u AND u.BajaLogica = 0', [':u' => $idUsuario]);
    }

    public function jugadorPorNombre(string $nombre): ?array
    {
        return $this->fila('SELECT ' . self::CAMPOS_JUGADOR . ' ' . self::DESDE_JUGADOR . ' WHERE u.NombreUsuario = :n AND u.BajaLogica = 0', [':n' => $nombre]);
    }

    /** Jugadores humanos cuyo nombre empieza con $prefijo (los % y _ se toman literales). */
    public function buscar(string $prefijo, int $excluir): array
    {
        $prefijo = addcslashes($prefijo, '%_\\');
        return $this->filas('SELECT u.IDUsuario, u.NombreUsuario, j.MMR, j.XP ' . self::DESDE_JUGADOR . "
            WHERE u.NombreUsuario LIKE :p AND u.Rol = 'jugador' AND j.EsBot = 0 AND u.BajaLogica = 0 AND u.IDUsuario <> :e
            ORDER BY u.NombreUsuario LIMIT 10", [':p' => $prefijo . '%', ':e' => $excluir]);
    }

    /* ---------- personalización del perfil (al estilo Discord) ---------- */

    public function perfilPersonal(int $idUsuario): ?array
    {
        $s = $this->db()->prepare('SELECT p.*, a.Personal AS Personaje FROM JUGADORES j LEFT JOIN PERFIL_PERSONAL p ON p.IDUsuario = j.IDUsuario
            LEFT JOIN APARIENCIA_JUGADOR a ON a.IDUsuario = j.IDUsuario WHERE j.IDUsuario = :u');
        $s->execute([':u' => $idUsuario]);
        $f = $s->fetch(PDO::FETCH_ASSOC);
        return $f === false ? null : $f;
    }

    public function guardarPerfilPersonal(int $idUsuario, array $c): void
    {
        $this->db()->prepare('INSERT INTO PERFIL_PERSONAL (IDUsuario, Banner1, Banner2, Acento, EstadoEmoji, EstadoTexto, Pronombres, Insignias, Marco)
            VALUES (:u, :b1, :b2, :ac, :ee, :et, :pr, :in, :ma)
            ON DUPLICATE KEY UPDATE Banner1 = VALUES(Banner1), Banner2 = VALUES(Banner2), Acento = VALUES(Acento), EstadoEmoji = VALUES(EstadoEmoji),
                EstadoTexto = VALUES(EstadoTexto), Pronombres = VALUES(Pronombres), Insignias = VALUES(Insignias), Marco = VALUES(Marco)')
            ->execute([':u' => $idUsuario, ':b1' => $c['banner1'], ':b2' => $c['banner2'], ':ac' => $c['acento'], ':ee' => $c['estadoEmoji'],
                ':et' => $c['estadoTexto'], ':pr' => $c['pronombres'], ':in' => implode(',', $c['insignias']), ':ma' => $c['marco']]);
    }

    public function logros(int $idUsuario): array
    {
        return array_column($this->filas('SELECT IDLogro FROM LOGROS_JUGADOR WHERE IDUsuario = :u', [':u' => $idUsuario]), 'IDLogro');
    }

    /* ---------- amistades ---------- */

    public function amistad(int $a, int $b): ?array
    {
        return $this->fila('SELECT IDSolicitante, IDDestino, Estado FROM AMISTADES WHERE ParMenor = LEAST(:a, :b) AND ParMayor = GREATEST(:a2, :b2)',
            [':a' => $a, ':b' => $b, ':a2' => $a, ':b2' => $b]);
    }

    public function pedirAmistad(int $de, int $para): void
    {
        $this->ejecutar('INSERT INTO AMISTADES (IDSolicitante, IDDestino) VALUES (:d, :p)', [':d' => $de, ':p' => $para]);
    }

    public function aceptarAmistad(int $solicitante, int $destino): bool
    {
        return $this->ejecutar("UPDATE AMISTADES SET Estado = 'aceptada', FechaRespuesta = NOW()
            WHERE IDSolicitante = :s AND IDDestino = :d AND Estado = 'pendiente'", [':s' => $solicitante, ':d' => $destino]) === 1;
    }

    public function borrarAmistad(int $a, int $b): void
    {
        $this->ejecutar('DELETE FROM AMISTADES WHERE ParMenor = LEAST(:a, :b) AND ParMayor = GREATEST(:a2, :b2)', [':a' => $a, ':b' => $b, ':a2' => $a, ':b2' => $b]);
    }

    public function amigos(int $idUsuario): array
    {
        return $this->filas('SELECT u.IDUsuario, u.NombreUsuario, j.MMR, j.XP, COALESCE(ap.Piel * 12 + ap.Pelo * 3 + ap.Gorro, 0) AS Apariencia, j.Bando
            FROM AMISTADES a INNER JOIN USUARIOS u ON u.IDUsuario = IF(a.IDSolicitante = :u, a.IDDestino, a.IDSolicitante)
            INNER JOIN JUGADORES j ON j.IDUsuario = u.IDUsuario LEFT JOIN APARIENCIA_JUGADOR ap ON ap.IDUsuario = u.IDUsuario
            WHERE (a.IDSolicitante = :u2 OR a.IDDestino = :u3) AND a.Estado = \'aceptada\' AND u.BajaLogica = 0
            ORDER BY u.NombreUsuario', [':u' => $idUsuario, ':u2' => $idUsuario, ':u3' => $idUsuario]);
    }

    /** Solicitudes pendientes: las que me mandaron y las que mandé. */
    public function solicitudes(int $idUsuario): array
    {
        return $this->filas("SELECT a.IDSolicitante, a.IDDestino, us.NombreUsuario AS Solicitante, ud.NombreUsuario AS Destino
            FROM AMISTADES a INNER JOIN USUARIOS us ON us.IDUsuario = a.IDSolicitante INNER JOIN USUARIOS ud ON ud.IDUsuario = a.IDDestino
            WHERE a.Estado = 'pendiente' AND (a.IDSolicitante = :u OR a.IDDestino = :u2) ORDER BY a.Fecha DESC", [':u' => $idUsuario, ':u2' => $idUsuario]);
    }

    /* ---------- bloqueos ---------- */

    public function bloqueado(int $a, int $b): bool
    {
        return $this->fila('SELECT 1 AS x FROM BLOQUEOS WHERE (IDUsuario = :a AND IDBloqueado = :b) OR (IDUsuario = :b2 AND IDBloqueado = :a2)',
            [':a' => $a, ':b' => $b, ':a2' => $a, ':b2' => $b]) !== null;
    }

    public function bloquear(int $idUsuario, int $idOtro): void
    {
        $this->ejecutar('INSERT IGNORE INTO BLOQUEOS (IDUsuario, IDBloqueado) VALUES (:u, :o)', [':u' => $idUsuario, ':o' => $idOtro]);
    }

    public function desbloquear(int $idUsuario, int $idOtro): void
    {
        $this->ejecutar('DELETE FROM BLOQUEOS WHERE IDUsuario = :u AND IDBloqueado = :o', [':u' => $idUsuario, ':o' => $idOtro]);
    }

    public function bloqueados(int $idUsuario): array
    {
        return $this->filas('SELECT b.IDBloqueado AS IDUsuario, u.NombreUsuario FROM BLOQUEOS b INNER JOIN USUARIOS u ON u.IDUsuario = b.IDBloqueado
            WHERE b.IDUsuario = :u ORDER BY u.NombreUsuario', [':u' => $idUsuario]);
    }

    /** A quiénes bloqueé y quiénes me bloquearon: con ninguno de ellos nos vemos en Berlín. */
    public function idsBloqueadosEntreAmbos(int $idUsuario): array
    {
        return array_map('intval', array_column($this->filas('SELECT IDBloqueado AS id FROM BLOQUEOS WHERE IDUsuario = :u
            UNION SELECT IDUsuario FROM BLOQUEOS WHERE IDBloqueado = :u2', [':u' => $idUsuario, ':u2' => $idUsuario]), 'id'));
    }

    /* ---------- presencia ---------- */

    /** [id => ['donde' => 'berlin'|'partida'|null, 'x', 'y', 'idPartida', 'estadoPartida', 'tipoPartida']] */
    public function presencias(array $ids): array
    {
        $r = [];
        $lista = self::lista($ids);
        foreach ($this->filas("SELECT IDUsuario, X, Y FROM POSICIONES_MUNDO WHERE IDUsuario IN ($lista) AND UltimaActividad > NOW(3) - INTERVAL 15 SECOND AND Visible = 1") as $f) {
            $r[(int) $f['IDUsuario']] = ['donde' => 'berlin', 'x' => (int) $f['X'], 'y' => (int) $f['Y']];
        }
        foreach ($this->filas("SELECT pa.IDUsuario, p.IDPartida, p.Estado, p.Tipo FROM PARTICIPACIONES pa INNER JOIN PARTIDAS p ON p.IDPartida = pa.IDPartida
            WHERE pa.IDUsuario IN ($lista) AND pa.Estado = 'activo' AND p.Estado IN ('esperando', 'en_curso') AND pa.UltimoLatido > NOW() - INTERVAL 45 SECOND") as $f) {
            $r[(int) $f['IDUsuario']] = ['donde' => 'partida', 'idPartida' => (int) $f['IDPartida'], 'estadoPartida' => $f['Estado'], 'tipoPartida' => $f['Tipo']];
        }
        return $r;
    }

    /* ---------- grupos ---------- */

    public function grupoDe(int $idUsuario): ?array
    {
        return $this->fila('SELECT g.IDGrupo, g.IDLider FROM GRUPO_MIEMBROS m INNER JOIN GRUPOS g ON g.IDGrupo = m.IDGrupo WHERE m.IDUsuario = :u', [':u' => $idUsuario]);
    }

    /** Bloquea la fila del grupo hasta el final de la transacción (para no pasarse de 4 con dos ingresos a la vez). */
    public function bloquearGrupo(int $idGrupo): ?array
    {
        return $this->fila('SELECT IDGrupo, IDLider FROM GRUPOS WHERE IDGrupo = :g FOR UPDATE', [':g' => $idGrupo]);
    }

    public function miembros(int $idGrupo): array
    {
        return $this->filas('SELECT m.IDUsuario, u.NombreUsuario FROM GRUPO_MIEMBROS m INNER JOIN USUARIOS u ON u.IDUsuario = m.IDUsuario
            WHERE m.IDGrupo = :g ORDER BY m.Fecha, m.IDUsuario', [':g' => $idGrupo]);
    }

    public function crearGrupo(int $idLider): int
    {
        $this->ejecutar('INSERT INTO GRUPOS (IDLider) VALUES (:l)', [':l' => $idLider]);
        $id = (int) $this->db()->lastInsertId();
        $this->agregarMiembro($id, $idLider);
        return $id;
    }

    public function agregarMiembro(int $idGrupo, int $idUsuario): void
    {
        $this->ejecutar('INSERT INTO GRUPO_MIEMBROS (IDUsuario, IDGrupo) VALUES (:u, :g)', [':u' => $idUsuario, ':g' => $idGrupo]);
    }

    public function quitarMiembro(int $idUsuario): void
    {
        $this->ejecutar('DELETE FROM GRUPO_MIEMBROS WHERE IDUsuario = :u', [':u' => $idUsuario]);
    }

    public function cambiarLider(int $idGrupo, int $idUsuario): void
    {
        $this->ejecutar('UPDATE GRUPOS SET IDLider = :u WHERE IDGrupo = :g', [':u' => $idUsuario, ':g' => $idGrupo]);
    }

    public function borrarGrupo(int $idGrupo): void
    {
        $this->ejecutar('DELETE FROM GRUPOS WHERE IDGrupo = :g', [':g' => $idGrupo]);   // en cascada: miembros, mensajes, invitaciones
    }

    public function mensajesGrupo(int $idGrupo, int $desde): array
    {
        return array_reverse($this->filas('SELECT m.IDMensaje, m.IDUsuario, u.NombreUsuario, m.Texto FROM MENSAJES_GRUPO m
            INNER JOIN USUARIOS u ON u.IDUsuario = m.IDUsuario WHERE m.IDGrupo = :g AND m.IDMensaje > :d ORDER BY m.IDMensaje DESC LIMIT 30',
            [':g' => $idGrupo, ':d' => $desde]));
    }

    public function decirGrupo(int $idGrupo, int $idUsuario, string $texto): void
    {
        $this->ejecutar('INSERT INTO MENSAJES_GRUPO (IDGrupo, IDUsuario, Texto) VALUES (:g, :u, :t)', [':g' => $idGrupo, ':u' => $idUsuario, ':t' => $texto]);
    }

    /* ---------- invitaciones ---------- */

    public function invitar(string $tipo, int $de, int $para, ?int $idGrupo, ?int $idPartida): void
    {
        $this->ejecutar('INSERT IGNORE INTO INVITACIONES (Tipo, IDInvitador, IDInvitado, IDGrupo, IDPartida) VALUES (:t, :d, :p, :g, :pa)',
            [':t' => $tipo, ':d' => $de, ':p' => $para, ':g' => $idGrupo, ':pa' => $idPartida]);
    }

    /** Invitaciones vigentes (las de mesas que ya empezaron o terminaron no se muestran). */
    public function invitacionesDe(int $idUsuario): array
    {
        return $this->filas("SELECT i.IDInvitacion, i.Tipo, i.IDGrupo, i.IDPartida, u.NombreUsuario AS Invitador, p.Nombre AS Partida
            FROM INVITACIONES i INNER JOIN USUARIOS u ON u.IDUsuario = i.IDInvitador LEFT JOIN PARTIDAS p ON p.IDPartida = i.IDPartida
            WHERE i.IDInvitado = :u AND (i.Tipo = 'grupo' OR p.Estado = 'esperando') ORDER BY i.Fecha DESC", [':u' => $idUsuario]);
    }

    public function invitacion(int $idInvitacion): ?array
    {
        return $this->fila('SELECT IDInvitacion, Tipo, IDInvitador, IDInvitado, IDGrupo, IDPartida FROM INVITACIONES WHERE IDInvitacion = :i', [':i' => $idInvitacion]);
    }

    public function borrarInvitacion(int $idInvitacion): void
    {
        $this->ejecutar('DELETE FROM INVITACIONES WHERE IDInvitacion = :i', [':i' => $idInvitacion]);
    }

    public function borrarInvitacionesEntre(int $a, int $b): void
    {
        $this->ejecutar('DELETE FROM INVITACIONES WHERE (IDInvitador = :a AND IDInvitado = :b) OR (IDInvitador = :b2 AND IDInvitado = :a2)',
            [':a' => $a, ':b' => $b, ':a2' => $a, ':b2' => $b]);
    }

    /* ---------- mirar partidas ---------- */

    /** Tipo, estado y jugadores humanos de una partida (para decidir si se puede mirar). */
    public function partidaVisible(int $idPartida): ?array
    {
        $p = $this->fila('SELECT IDPartida, Tipo, Estado, Nombre FROM PARTIDAS WHERE IDPartida = :p', [':p' => $idPartida]);
        if ($p === null) {
            return null;
        }
        $p['jugadores'] = array_map('intval', array_column($this->filas('SELECT IDUsuario FROM PARTICIPACIONES WHERE IDPartida = :p', [':p' => $idPartida]), 'IDUsuario'));
        return $p;
    }

    public function partidasEnCursoDeDistrito(int $idDistrito): array
    {
        return $this->filas("SELECT p.IDPartida, p.Nombre, p.Tipo, (SELECT COUNT(*) FROM PARTICIPACIONES x WHERE x.IDPartida = p.IDPartida) AS Jugadores
            FROM PARTIDAS p INNER JOIN PARTIDA_DISTRITO d ON d.IDPartida = p.IDPartida
            WHERE d.IDDistrito = :d AND p.Estado = 'en_curso' AND p.Tipo = 'publica' ORDER BY p.IDPartida DESC LIMIT 10", [':d' => $idDistrito]);
    }
}
?>
