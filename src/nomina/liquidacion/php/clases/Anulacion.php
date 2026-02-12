<?php

namespace Src\Nomina\Liquidacion\Php\Clases;

use Config\Clases\Conexion;

use PDO;
use PDOException;
use Src\Nomina\Empleados\Php\Clases\Contratos;

class Anulacion
{
    private $conexion;

    public function __construct($conexion = null)
    {
        $this->conexion = $conexion ?: Conexion::getConexion(); // Método estático que retorna el objeto PDO
    }

    /**
     * Elimina un registro.
     *
     * @param int $id ID del registro a eliminar
     * @return string Mensaje de éxito o error
     */

    /**
     * Anula (marca como estado=0) todas las líneas de liquidación relacionadas
     * con un empleado en una nómina específica. Ejecuta todas las consultas
     * dentro de una transacción para garantizar atomicidad.
     *
     * @param int $id_empleado ID del empleado
     * @param int $id_nomina ID de la nómina
     * @return string 'si' en caso de éxito o mensaje de error
     */
    public function anulaRegistros($id_empleado, $id_nomina, $tipo = 1)
    {

        if ($tipo == 1) {
            $tipo = " AND `tipo`  = 'S'";
            $tp = " AND `nllm`.`tipo`  = 'S'";
        } else {
            $tipo = "";
            $tp = "";
        }
        $nomina = (new Nomina())->getRegistro($id_nomina);
        if ($nomina['tipo'] == 'PS') {
            $contrato = (new Liquidacion())->getEmpleadosLiq($id_nomina, [$id_empleado]);
            $response = (new Contratos())->editEstadoContrato($contrato[0]['id_contrato'], 1);
        }

        $queries = [
            // horas extra
            "UPDATE `nom_liq_horex` AS `nlhe`
                INNER JOIN `nom_horas_ex_trab` AS `nhet` ON `nlhe`.`id_he_lab` = `nhet`.`id_he_trab`
             SET `nlhe`.`estado` = 0
             WHERE `nhet`.`id_empleado` = :id_empleado AND `nlhe`.`id_nomina` = :id_nomina",

            // bsp
            "UPDATE `nom_liq_bsp` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina $tipo",

            // cesantias
            "UPDATE `nom_liq_cesantias` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina $tipo",

            // compesatorio
            "UPDATE `nom_liq_compesatorio` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina",

            // descuentos (join con otros descuentos)
            "UPDATE `nom_liq_descuento` AS `nld`
                INNER JOIN `nom_otros_descuentos` AS `nod` ON `nld`.`id_dcto` = `nod`.`id_dcto`
             SET `nld`.`estado` = 0
             WHERE `nod`.`id_empleado` = :id_empleado AND `nld`.`id_nomina` = :id_nomina",

            "UPDATE `nom_liq_dias_lab` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina",
            "UPDATE `nom_liq_dlab_auxt` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina $tipo",

            // embargos
            "UPDATE `nom_liq_embargo` AS `nle`
                INNER JOIN `nom_embargos` AS `ne` ON `nle`.`id_embargo` = `ne`.`id_embargo`
             SET `nle`.`estado` = 0
             WHERE `ne`.`id_empleado` = :id_empleado AND `nle`.`id_nomina` = :id_nomina",

            //"UPDATE `nom_liq_empleado` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina",

            // incapacidades
            "UPDATE `nom_liq_incap` AS `nli`
                INNER JOIN `nom_incapacidad` AS `ni` ON `nli`.`id_incapacidad` = `ni`.`id_incapacidad`
             SET `nli`.`estado` = 0
             WHERE `ni`.`id_empleado` = :id_empleado AND `nli`.`id_nomina` = :id_nomina",

            // indemnizaciones por vacaciones
            "UPDATE `nom_liq_indemniza_vac` AS `nliv`
                INNER JOIN `nom_indemniza_vac` AS `niv` ON `nliv`.`id_indemnizacion` = `niv`.`id_indemniza`
             SET `nliv`.`estado` = 0
             WHERE `niv`.`id_empleado` = :id_empleado AND `nliv`.`id_nomina` = :id_nomina",

            // libranzas
            "UPDATE `nom_liq_libranza` AS `nll`
                INNER JOIN `nom_libranzas` AS `nl` ON `nll`.`id_libranza` = `nl`.`id_libranza`
             SET `nll`.`estado` = 0
             WHERE `nl`.`id_empleado` = :id_empleado AND `nll`.`id_nomina` = :id_nomina",

            // licencia luto
            "UPDATE `nom_liq_licluto` AS `nlll`
                INNER JOIN `nom_licencia_luto` AS `nll` ON `nlll`.`id_licluto` = `nll`.`id_licluto`
             SET `nlll`.`estado` = 0
             WHERE `nll`.`id_empleado` = :id_empleado AND `nlll`.`id_nomina` = :id_nomina",

            //viaticos
            "UPDATE `nom_liq_viaticos` AS `nlv`
                INNER JOIN `nom_viaticos` AS `nv` ON `nlv`.`id_viatico` = `nv`.`id_viatico`
             SET `nlv`.`estado` = 0
             WHERE `nv`.`id_empleado` = :id_empleado AND `nlv`.`id_nomina` = :id_nomina",
            // licencias mp
            "UPDATE `nom_liq_licmp` AS `nllm`
                INNER JOIN `nom_licenciasmp` AS `nlm` ON `nllm`.`id_licmp` = `nlm`.`id_licmp`
             SET `nllm`.`estado` = 0
             WHERE `nlm`.`id_empleado` = :id_empleado AND `nllm`.`id_nomina` = :id_nomina $tp",

            // licencias nr
            "UPDATE `nom_liq_licnr` AS `nlln`
                INNER JOIN `nom_licenciasnr` AS `nln` ON `nlln`.`id_licnr` = `nln`.`id_licnr`
             SET `nlln`.`estado` = 0
             WHERE `nln`.`id_empleado` = :id_empleado AND `nlln`.`id_nomina` = :id_nomina",

            // sindicato
            "UPDATE `nom_liq_sindicato_aportes` AS `nlsa`
                INNER JOIN `nom_cuota_sindical` AS `ncs` ON `nlsa`.`id_cuota_sindical` = `ncs`.`id_cuota_sindical`
             SET `nlsa`.`estado` = 0
             WHERE `ncs`.`id_empleado` = :id_empleado AND `nlsa`.`id_nomina` = :id_nomina",

            // vacaciones
            "UPDATE `nom_liq_vac` AS `nlv`
                INNER JOIN `nom_vacaciones` AS `nv` ON `nlv`.`id_vac` = `nv`.`id_vac`
             SET `nlv`.`estado` = 0
             WHERE `nv`.`id_empleado` = :id_empleado AND `nlv`.`id_nomina` = :id_nomina AND `nlv`.`tipo`  = 'S'",

            // parafiscales y prestaciones
            "UPDATE `nom_liq_parafiscales` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina",
            "UPDATE `nom_liq_prestaciones_sociales` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina",

            // primas
            "UPDATE `nom_liq_prima` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina $tipo",
            "UPDATE `nom_liq_prima_nav` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina $tipo",

            // salario, seg social y retenciones
            "UPDATE `nom_liq_salario` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina",
            "UPDATE `nom_liq_segsocial_empdo` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina",
            "UPDATE `nom_retencion_fte` SET `estado` = 0 WHERE `id_empleado` = :id_empleado AND `id_nomina` = :id_nomina"
        ];

        try {
            // Inicio de transacción
            // verificar si hay transacción activa
            if (!$this->conexion->inTransaction()) {
                $this->conexion->beginTransaction();
            }

            foreach ($queries as $sql) {
                $stmt = $this->conexion->prepare($sql);
                $stmt->bindValue(':id_empleado', $id_empleado, PDO::PARAM_INT);
                $stmt->bindValue(':id_nomina', $id_nomina, PDO::PARAM_INT);
                $stmt->execute();
            }

            $this->conexion->commit();
            return 'si';
        } catch (PDOException $e) {
            try {
                $this->conexion->rollBack();
            } catch (PDOException $rbe) {
                return 'Error Rollback: ' . $rbe->getMessage();
            }
            //identificar cual es la consulta que sale con error que se esta ejecutando
            return 'Error SQL: ' . $e->getMessage() . ' en la consulta: ' . $sql;
        }
    }
}
