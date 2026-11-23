<?php

require_once(__DIR__ . '/../DTO/PartidasDTO.php');
require_once(__DIR__ . '/../DTO/EstadoPartidaDTO.php');
require_once('IPersistenciaPartidas.php');
require_once(__DIR__ . '/../Conexion/ConexionBD.php');

/*
 * Todo el SQL de las partidas en línea. Singleton, como el resto.
 * Usa la conexión compartida para que PartidaLogica pueda envolver varias
 * operaciones en una sola transacción (iniciarTransaccion / confirmar).
 *
 * Los códigos ('cabo', 'fabrica', 'lado_izquierdo') se traducen a los IDs
 * de la base acá adentro: la lógica nunca ve un IDFicha ni un IDRecinto.
 */
class PersistenciaPartidas implements IPersistenciaPartidas
{
    private ?PDO $conn = null;
    private array $idFicha = [];
    private array $codigoFicha = [];
    private array $idRecinto = [];
    private array $codigoRecinto = [];
    private array $idCara = [];
    private array $codigoCara = [];
    private array $imagenFicha = [];

    private static ?PersistenciaPartidas $instancia = null;

    public static function getInstancia(): PersistenciaPartidas
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
        throw new \Exception("No se puede deserializar el singletonPartidas");
    }

    private function __construct()
    {
        try {
            $this->conn = ConexionBD::compartida();
            $this->cargarCatalogos();
        } catch (Exception $e) {
            error_log("Error de conexión en PersistenciaPartidas: " . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        if ($this->conn === null) {
            throw new RuntimeException('Las partidas no están disponibles por un problema de conexión.');
        }
        return $this->conn;
    }

    private function cargarCatalogos(): void
    {
        foreach ($this->db()->query('SELECT IDFicha, Codigo, Imagen FROM FICHAS') as $fila) {
            $this->idFicha[$fila['Codigo']] = (int) $fila['IDFicha'];
            $this->codigoFicha[(int) $fila['IDFicha']] = $fila['Codigo'];
            $this->imagenFicha[$fila['Codigo']] = $fila['Imagen'];
        }
        foreach ($this->db()->query('SELECT IDRecinto, Codigo FROM RECINTOS') as $fila) {
            $this->idRecinto[$fila['Codigo']] = (int) $fila['IDRecinto'];
            $this->codigoRecinto[(int) $fila['IDRecinto']] = $fila['Codigo'];
        }
        foreach ($this->db()->query('SELECT IDCara, Codigo FROM CARAS_DADO') as $fila) {
            $this->idCara[$fila['Codigo']] = (int) $fila['IDCara'];
            $this->codigoCara[(int) $fila['IDCara']] = $fila['Codigo'];
        }
    }

    public function imagenesFichas(): array
    {
        return $this->imagenFicha;
    }

    /* ---------- Transacciones ---------- */

    public function iniciarTransaccion(): void
    {
        $this->db()->beginTransaction();
    }

    public function confirmar(): void
    {
        if ($this->db()->inTransaction()) {
            $this->db()->commit();
        }
    }

    public function revertir(): void
    {
        if ($this->db()->inTransaction()) {
            $this->db()->rollBack();
        }
    }

    /** Bloquea la fila de la partida hasta el fin de la transacción (nadie más la toca). */
    public function bloquear(int $idPartida): bool
    {
        $s = $this->db()->prepare('SELECT IDPartida FROM PARTIDAS WHERE IDPartida = :id AND BajaLogica = 0 FOR UPDATE');
        $s->execute([':id' => $idPartida]);
        return $s->fetchColumn() !== false;
    }

    /** Versión actual, sin bloquear (para responder rápido "no hay cambios"). */
    public function version(int $idPartida): ?int
    {
        $s = $this->db()->prepare('SELECT Version FROM PARTIDAS WHERE IDPartida = :id AND BajaLogica = 0');
        $s->execute([':id' => $idPartida]);
        $v = $s->fetchColumn();
        return $v === false ? null : (int) $v;
    }

    /* ---------- Salas ---------- */

    public function crearPartida(string $nombre, string $tipo, ?string $hashContra, string $modo, int $maxJugadores, int $idCreador, ConfigPartida $config): int
    {
        $db = $this->db();
        $s = $db->prepare(
            'INSERT INTO PARTIDAS (Nombre, Tipo, HashContra, Modo, Estado, MaxJugadores, IDCreador, InicioFase)
             VALUES (:nombre, :tipo, :hash, :modo, \'esperando\', :max, :creador, NOW())'
        );
        $s->execute([
            ':nombre' => $nombre, ':tipo' => $tipo, ':hash' => $hashContra, ':modo' => $modo,
            ':max' => $maxJugadores, ':creador' => $idCreador,
        ]);
        $id = (int) $db->lastInsertId();

        $c = $config->aArreglo();
        $s = $db->prepare(
            'INSERT INTO CONFIG_PARTIDA (IDPartida, Rondas, FichasPorRonda, CapacidadMuro, SegundosTurno,
                ValorTipo1, ValorTipo2, ValorTipo3, PuntosEspias, ReglaPolitico, TiradorExento, PermiteBots)
             VALUES (:id, :rondas, :fichas, :muro, :segundos, :v1, :v2, :v3, :espias, :politico, :exento, :bots)'
        );
        $s->execute([
            ':id' => $id, ':rondas' => $c['rondas'], ':fichas' => $c['fichasPorRonda'], ':muro' => $c['capacidadMuro'],
            ':segundos' => $c['segundosTurno'], ':v1' => $c['valorTipo1'], ':v2' => $c['valorTipo2'], ':v3' => $c['valorTipo3'],
            ':espias' => $c['puntosEspias'], ':politico' => (int) $c['reglaPolitico'], ':exento' => (int) $c['tiradorExento'],
            ':bots' => (int) $c['permiteBots'],
        ]);
        $s = $db->prepare('INSERT INTO PARTIDA_CARAS (IDPartida, IDCara) VALUES (:id, :cara)');
        foreach ($c['caras'] as $cara) {
            $s->execute([':id' => $id, ':cara' => $this->idCara[$cara]]);
        }
        $this->agregarParticipante($id, $idCreador, 0);
        return $id;
    }

    public function agregarParticipante(int $idPartida, int $idUsuario, int $asiento): void
    {
        $s = $this->db()->prepare(
            'INSERT INTO PARTICIPACIONES (IDPartida, IDUsuario, Asiento, UltimoLatido) VALUES (:p, :u, :a, NOW())'
        );
        $s->execute([':p' => $idPartida, ':u' => $idUsuario, ':a' => $asiento]);
    }

    public function quitarParticipante(int $idPartida, int $idUsuario): void
    {
        $s = $this->db()->prepare('DELETE FROM PARTICIPACIONES WHERE IDPartida = :p AND IDUsuario = :u');
        $s->execute([':p' => $idPartida, ':u' => $idUsuario]);
    }

    /** Partida en espera o en curso donde el usuario sigue sentado (o null). */
    public function partidaActivaDeUsuario(int $idUsuario): ?int
    {
        $s = $this->db()->prepare(
            "SELECT p.IDPartida FROM PARTIDAS p
             INNER JOIN PARTICIPACIONES pa ON pa.IDPartida = p.IDPartida
             WHERE pa.IDUsuario = :u AND pa.Estado = 'activo' AND p.Estado IN ('esperando', 'en_curso') AND p.BajaLogica = 0
             ORDER BY p.IDPartida DESC LIMIT 1"
        );
        $s->execute([':u' => $idUsuario]);
        $id = $s->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** Salas esperando jugadores (las competitivas no se listan: las arma la cola). */
    public function listarSalas(): array
    {
        $sql = "SELECT p.IDPartida, p.Nombre, p.Tipo, p.Modo, p.MaxJugadores, u.NombreUsuario AS Creador,
                       COUNT(pa.IDUsuario) AS Jugadores
                FROM PARTIDAS p
                INNER JOIN PARTICIPACIONES pa ON pa.IDPartida = p.IDPartida
                LEFT JOIN USUARIOS u ON u.IDUsuario = p.IDCreador
                WHERE p.Estado = 'esperando' AND p.Modo <> 'competitiva' AND p.BajaLogica = 0
                GROUP BY p.IDPartida, p.Nombre, p.Tipo, p.Modo, p.MaxJugadores, u.NombreUsuario
                HAVING COUNT(pa.IDUsuario) < p.MaxJugadores
                ORDER BY p.IDPartida DESC
                LIMIT 50";
        return $this->db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function datosSala(int $idPartida): ?array
    {
        $s = $this->db()->prepare(
            "SELECT p.IDPartida, p.Estado, p.Tipo, p.HashContra, p.Modo, p.MaxJugadores, p.IDCreador,
                    (SELECT COUNT(*) FROM PARTICIPACIONES pa WHERE pa.IDPartida = p.IDPartida) AS Jugadores,
                    (SELECT COALESCE(MAX(Asiento) + 1, 0) FROM PARTICIPACIONES pa WHERE pa.IDPartida = p.IDPartida) AS ProximoAsiento
             FROM PARTIDAS p WHERE p.IDPartida = :id AND p.BajaLogica = 0"
        );
        $s->execute([':id' => $idPartida]);
        $fila = $s->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    /** Bots que no están sentados en ninguna partida activa. */
    public function botsLibres(): array
    {
        // Solo los bots genéricos ("Bot ..."): los de los contactos juegan únicamente sus desafíos.
        $sql = "SELECT j.IDUsuario FROM JUGADORES j INNER JOIN USUARIOS u ON u.IDUsuario = j.IDUsuario
                WHERE j.EsBot = 1 AND j.BajaLogica = 0 AND u.NombreUsuario LIKE 'Bot %' AND j.IDUsuario NOT IN (
                    SELECT pa.IDUsuario FROM PARTICIPACIONES pa
                    INNER JOIN PARTIDAS p ON p.IDPartida = pa.IDPartida
                    WHERE p.Estado IN ('esperando', 'en_curso') AND pa.Estado = 'activo')
                ORDER BY RAND()";
        return array_map('intval', $this->db()->query($sql)->fetchAll(PDO::FETCH_COLUMN));
    }

    /* ---------- Estado completo ---------- */

    public function cargarEstado(int $idPartida): ?EstadoPartida
    {
        $db = $this->db();
        $s = $db->prepare(
            'SELECT p.*, TIMESTAMPDIFF(SECOND, p.InicioFase, NOW()) AS segundosFase,
                    c.Rondas, c.FichasPorRonda, c.CapacidadMuro, c.SegundosTurno, c.ValorTipo1, c.ValorTipo2,
                    c.ValorTipo3, c.PuntosEspias, c.ReglaPolitico, c.TiradorExento, c.PermiteBots
             FROM PARTIDAS p INNER JOIN CONFIG_PARTIDA c ON c.IDPartida = p.IDPartida
             WHERE p.IDPartida = :id AND p.BajaLogica = 0'
        );
        $s->execute([':id' => $idPartida]);
        $fila = $s->fetch(PDO::FETCH_ASSOC);
        if ($fila === false) {
            return null;
        }

        $s = $db->prepare('SELECT IDCara FROM PARTIDA_CARAS WHERE IDPartida = :id');
        $s->execute([':id' => $idPartida]);
        $caras = array_map(fn($id) => $this->codigoCara[(int) $id], $s->fetchAll(PDO::FETCH_COLUMN));

        $config = ConfigPartida::desdeArreglo([
            'rondas' => $fila['Rondas'], 'fichasPorRonda' => $fila['FichasPorRonda'], 'capacidadMuro' => $fila['CapacidadMuro'],
            'segundosTurno' => $fila['SegundosTurno'], 'valorTipo1' => $fila['ValorTipo1'], 'valorTipo2' => $fila['ValorTipo2'],
            'valorTipo3' => $fila['ValorTipo3'], 'puntosEspias' => $fila['PuntosEspias'],
            'reglaPolitico' => (int) $fila['ReglaPolitico'], 'tiradorExento' => (int) $fila['TiradorExento'],
            'permiteBots' => (int) $fila['PermiteBots'], 'caras' => $caras,
        ]);
        $fila['cara'] = $fila['IDCara'] === null ? null : $this->codigoCara[(int) $fila['IDCara']];

        $s = $db->prepare(
            'SELECT pa.IDUsuario, u.NombreUsuario AS Nombre, pa.Asiento, pa.Estado, pa.Confirmado, j.EsBot, j.MMR,
                    TIMESTAMPDIFF(SECOND, pa.UltimoLatido, NOW()) AS SegundosSinLatido,
                    pa.PuntajeFinal, pa.Posicion, pa.PuntosOtorgados, pa.MMRAntes, pa.MMRDespues
             FROM PARTICIPACIONES pa
             INNER JOIN USUARIOS u ON u.IDUsuario = pa.IDUsuario
             INNER JOIN JUGADORES j ON j.IDUsuario = pa.IDUsuario
             WHERE pa.IDPartida = :id ORDER BY pa.Asiento'
        );
        $s->execute([':id' => $idPartida]);
        $participantes = $s->fetchAll(PDO::FETCH_ASSOC);
        foreach ($participantes as &$p) {
            foreach (['IDUsuario', 'Asiento', 'Confirmado', 'EsBot', 'MMR'] as $campo) {
                $p[$campo] = (int) $p[$campo];
            }
            $p['SegundosSinLatido'] = $p['SegundosSinLatido'] === null ? 0 : (int) $p['SegundosSinLatido'];
        }
        unset($p);

        $s = $db->prepare(
            'SELECT NroFicha, IDFicha, Ubicacion, IDUsuario, IDRecinto, Posicion, RondaColocada, TurnoColocada
             FROM FICHAS_PARTIDA WHERE IDPartida = :id ORDER BY NroFicha'
        );
        $s->execute([':id' => $idPartida]);
        $fichas = [];
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $fichas[] = [
                'nro' => (int) $f['NroFicha'],
                'tipo' => $this->codigoFicha[(int) $f['IDFicha']],
                'ubicacion' => $f['Ubicacion'],
                'idUsuario' => $f['IDUsuario'] === null ? null : (int) $f['IDUsuario'],
                'recinto' => $f['IDRecinto'] === null ? null : $this->codigoRecinto[(int) $f['IDRecinto']],
                'posicion' => $f['Posicion'] === null ? null : (int) $f['Posicion'],
                'ronda' => $f['RondaColocada'] === null ? null : (int) $f['RondaColocada'],
                'turno' => $f['TurnoColocada'] === null ? null : (int) $f['TurnoColocada'],
            ];
        }

        $s = $db->prepare(
            'SELECT IDJugada, NroFicha, IDUsuario, IDRecinto, IDRecintoOrigen, Tipo, Ronda, Turno
             FROM JUGADAS WHERE IDPartida = :id ORDER BY IDJugada'
        );
        $s->execute([':id' => $idPartida]);
        $jugadas = [];
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $j) {
            $jugadas[] = [
                'id' => (int) $j['IDJugada'],
                'nro' => (int) $j['NroFicha'],
                'idUsuario' => (int) $j['IDUsuario'],
                'recinto' => $this->codigoRecinto[(int) $j['IDRecinto']],
                'origen' => $j['IDRecintoOrigen'] === null ? null : $this->codigoRecinto[(int) $j['IDRecintoOrigen']],
                'tipo' => $j['Tipo'],
                'ronda' => (int) $j['Ronda'],
                'turno' => (int) $j['Turno'],
            ];
        }

        return new EstadoPartida($fila, $config, $participantes, $fichas, $jugadas);
    }

    /* ---------- Escrituras ---------- */

    private const CAMPOS_PARTIDA = ['Estado', 'MaxJugadores', 'IDCreador', 'RondaActual', 'TurnoActual', 'Fase', 'AsientoDado'];

    /**
     * Actualiza columnas de PARTIDAS y sube la versión.
     * $fechasAhora: columnas de fecha que se ponen en NOW() ('InicioFase', 'FechaInicio', 'FechaFin').
     * $cara: código de la cara del dado; '' la borra; null no la toca.
     */
    public function actualizarPartida(int $idPartida, array $campos, array $fechasAhora = [], ?string $cara = null): void
    {
        $sets = ['Version = Version + 1'];
        $parametros = [':id' => $idPartida];
        foreach ($campos as $campo => $valor) {
            if (!in_array($campo, self::CAMPOS_PARTIDA, true)) {
                throw new InvalidArgumentException("Campo de partida no permitido: $campo");
            }
            $sets[] = "$campo = :$campo";
            $parametros[":$campo"] = $valor;
        }
        foreach ($fechasAhora as $campo) {
            if (!in_array($campo, ['InicioFase', 'FechaInicio', 'FechaFin'], true)) {
                throw new InvalidArgumentException("Fecha no permitida: $campo");
            }
            $sets[] = "$campo = NOW()";
        }
        if ($cara === '') {
            $sets[] = 'IDCara = NULL';
        } elseif ($cara !== null) {
            $sets[] = 'IDCara = :cara';
            $parametros[':cara'] = $this->idCara[$cara];
        }
        $s = $this->db()->prepare('UPDATE PARTIDAS SET ' . implode(', ', $sets) . ' WHERE IDPartida = :id');
        $s->execute($parametros);
    }

    public function sumarVersion(int $idPartida): void
    {
        $this->db()->prepare('UPDATE PARTIDAS SET Version = Version + 1 WHERE IDPartida = :id')->execute([':id' => $idPartida]);
    }

    private const CAMPOS_PARTICIPACION = ['Estado', 'Confirmado', 'PuntajeFinal', 'Posicion', 'PuntosOtorgados', 'MMRAntes', 'MMRDespues'];

    public function actualizarParticipacion(int $idPartida, int $idUsuario, array $campos): void
    {
        $sets = [];
        $parametros = [':p' => $idPartida, ':u' => $idUsuario];
        foreach ($campos as $campo => $valor) {
            if (!in_array($campo, self::CAMPOS_PARTICIPACION, true)) {
                throw new InvalidArgumentException("Campo de participación no permitido: $campo");
            }
            $sets[] = "$campo = :$campo";
            $parametros[":$campo"] = is_bool($valor) ? (int) $valor : $valor;
        }
        $s = $this->db()->prepare('UPDATE PARTICIPACIONES SET ' . implode(', ', $sets) . ' WHERE IDPartida = :p AND IDUsuario = :u');
        $s->execute($parametros);
    }

    public function desconfirmarTodos(int $idPartida): void
    {
        $this->db()->prepare('UPDATE PARTICIPACIONES SET Confirmado = 0 WHERE IDPartida = :p')->execute([':p' => $idPartida]);
    }

    /** Reasigna asientos (al empezar se mezclan). $orden = lista de IDUsuario en el orden nuevo. */
    public function reasignarAsientos(int $idPartida, array $orden): void
    {
        $db = $this->db();
        $db->prepare('UPDATE PARTICIPACIONES SET Asiento = Asiento + 50 WHERE IDPartida = :p')->execute([':p' => $idPartida]);
        $s = $db->prepare('UPDATE PARTICIPACIONES SET Asiento = :a WHERE IDPartida = :p AND IDUsuario = :u');
        foreach (array_values($orden) as $asiento => $idUsuario) {
            $s->execute([':a' => $asiento, ':p' => $idPartida, ':u' => $idUsuario]);
        }
    }

    public function latido(int $idPartida, int $idUsuario): void
    {
        $s = $this->db()->prepare('UPDATE PARTICIPACIONES SET UltimoLatido = NOW() WHERE IDPartida = :p AND IDUsuario = :u');
        $s->execute([':p' => $idPartida, ':u' => $idUsuario]);
    }

    public function latidoTodos(int $idPartida): void
    {
        $this->db()->prepare('UPDATE PARTICIPACIONES SET UltimoLatido = NOW() WHERE IDPartida = :p')->execute([':p' => $idPartida]);
    }

    /** Guarda la bolsa mezclada: una fila por ficha física, numeradas desde 1. */
    public function insertarBolsa(int $idPartida, array $tipos): void
    {
        $s = $this->db()->prepare('INSERT INTO FICHAS_PARTIDA (IDPartida, NroFicha, IDFicha) VALUES (:p, :n, :f)');
        foreach (array_values($tipos) as $i => $tipo) {
            $s->execute([':p' => $idPartida, ':n' => $i + 1, ':f' => $this->idFicha[$tipo]]);
        }
    }

    /** Mueve una ficha física: a la mano de alguien, al tablero, o la retira. */
    public function actualizarFicha(int $idPartida, int $nro, string $ubicacion, ?int $idUsuario, ?string $recinto = null, ?int $posicion = null, ?int $ronda = null, ?int $turno = null): void
    {
        $s = $this->db()->prepare(
            'UPDATE FICHAS_PARTIDA SET Ubicacion = :ub, IDUsuario = :u, IDRecinto = :r, Posicion = :pos,
                    RondaColocada = :ronda, TurnoColocada = :turno
             WHERE IDPartida = :p AND NroFicha = :n'
        );
        $s->execute([
            ':ub' => $ubicacion, ':u' => $idUsuario, ':r' => $recinto === null ? null : $this->idRecinto[$recinto],
            ':pos' => $posicion, ':ronda' => $ronda, ':turno' => $turno, ':p' => $idPartida, ':n' => $nro,
        ]);
    }

    /** Reescribe recinto y posición de todas las fichas de un tablero (después de mover o de la X). */
    public function guardarTablero(int $idPartida, array $tablero): void
    {
        $s = $this->db()->prepare(
            'UPDATE FICHAS_PARTIDA SET IDRecinto = :r, Posicion = :pos WHERE IDPartida = :p AND NroFicha = :n'
        );
        foreach ($tablero as $f) {
            $s->execute([':r' => $this->idRecinto[$f['recinto']], ':pos' => $f['posicion'], ':p' => $idPartida, ':n' => $f['nro']]);
        }
    }

    /** Cambia el dueño de fichas en la mano (el paso a la izquierda). $nuevoDuenoPorNro = [nro => IDUsuario]. */
    public function pasarFichasDeMano(int $idPartida, array $nuevoDuenoPorNro): void
    {
        $s = $this->db()->prepare('UPDATE FICHAS_PARTIDA SET IDUsuario = :u WHERE IDPartida = :p AND NroFicha = :n');
        foreach ($nuevoDuenoPorNro as $nro => $idUsuario) {
            $s->execute([':u' => $idUsuario, ':p' => $idPartida, ':n' => $nro]);
        }
    }

    public function insertarJugada(int $idPartida, int $nro, int $idUsuario, string $recinto, ?string $origen, string $tipo, int $ronda, int $turno): void
    {
        $s = $this->db()->prepare(
            'INSERT INTO JUGADAS (IDPartida, NroFicha, IDUsuario, IDRecinto, IDRecintoOrigen, Tipo, Ronda, Turno)
             VALUES (:p, :n, :u, :r, :o, :t, :ronda, :turno)'
        );
        $s->execute([
            ':p' => $idPartida, ':n' => $nro, ':u' => $idUsuario, ':r' => $this->idRecinto[$recinto],
            ':o' => $origen === null ? null : $this->idRecinto[$origen], ':t' => $tipo, ':ronda' => $ronda, ':turno' => $turno,
        ]);
    }

    public function borrarColocacion(int $idPartida, int $nro, int $ronda, int $turno): void
    {
        $s = $this->db()->prepare(
            "DELETE FROM JUGADAS WHERE IDPartida = :p AND NroFicha = :n AND Ronda = :r AND Turno = :t AND Tipo IN ('colocar', 'auto')"
        );
        $s->execute([':p' => $idPartida, ':n' => $nro, ':r' => $ronda, ':t' => $turno]);
    }

    /* ---------- Resultados ---------- */

    /**
     * Suma una partida terminada a las estadísticas del jugador.
     * $puntosTienda va al saldo; $puntosRanking a los puntos históricos.
     * $mmrNuevo solo en competitivo.
     */
    public function registrarResultado(int $idUsuario, bool $gano, int $puntosTienda, int $puntosRanking, ?int $mmrNuevo = null): void
    {
        $sql = 'UPDATE JUGADORES SET PartidasJugadas = PartidasJugadas + 1,
                    PartidasGanadas = PartidasGanadas + :gano,
                    PntsPartida = PntsPartida + :tienda,
                    PuntosHistoricos = PuntosHistoricos + :ranking'
            . ($mmrNuevo !== null ? ', MMR = :mmr, PartidasCompetitivas = PartidasCompetitivas + 1' : '')
            . ' WHERE IDUsuario = :u AND EsBot = 0';
        $parametros = [':gano' => $gano ? 1 : 0, ':tienda' => max(0, $puntosTienda), ':ranking' => max(0, $puntosRanking), ':u' => $idUsuario];
        if ($mmrNuevo !== null) {
            $parametros[':mmr'] = max(0, $mmrNuevo);
        }
        $this->db()->prepare($sql)->execute($parametros);
    }

    /** Últimas partidas terminadas del jugador, para el historial del inicio. */
    public function historialJugador(int $idUsuario, int $limite = 10): array
    {
        $s = $this->db()->prepare(
            "SELECT p.IDPartida, p.Nombre, p.Modo, p.FechaFin, pa.Posicion, pa.PuntajeFinal,
                    pa.PuntosOtorgados, pa.Estado, pa.MMRAntes, pa.MMRDespues,
                    (SELECT COUNT(*) FROM PARTICIPACIONES x WHERE x.IDPartida = p.IDPartida) AS Jugadores
             FROM PARTICIPACIONES pa INNER JOIN PARTIDAS p ON p.IDPartida = pa.IDPartida
             WHERE pa.IDUsuario = :u AND p.Estado = 'finalizada'
             ORDER BY p.FechaFin DESC LIMIT " . max(1, min(50, $limite))
        );
        $s->execute([':u' => $idUsuario]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Imagen de cada ficha para cada jugador: [IDUsuario => [codigoFicha => URL de la skin]]. */
    public function skinsPorJugador(array $idsUsuarios): array
    {
        $resultado = [];
        foreach ($idsUsuarios as $id) {
            $resultado[$id] = [];
        }
        if ($idsUsuarios === []) {
            return $resultado;
        }
        $marcadores = implode(',', array_fill(0, count($idsUsuarios), '?'));
        $s = $this->db()->prepare(
            "SELECT se.IDUsuario, se.IDFicha, s.URL FROM skins_equipadas se
             INNER JOIN SKINS s ON s.IDSkin = se.IDSkin AND s.IDFicha = se.IDFicha AND s.BajaLogica = 0
             WHERE se.IDUsuario IN ($marcadores)"
        );
        $s->execute(array_values($idsUsuarios));
        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $resultado[(int) $fila['IDUsuario']][$this->codigoFicha[(int) $fila['IDFicha']]] = $fila['URL'];
        }
        return $resultado;
    }
}
?>
