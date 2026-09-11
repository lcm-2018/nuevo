# Reporte de descuentos por cuenta en un periodo

## Objetivo
Generar un informe de retenciones para que su generación se base exclusivamente en los pagos realizados durante el período seleccionado, asociando a cada pago los descuentos correspondientes de la causación que origina la obligación.

## Formato 
**Corte:** ene a marzo

### Banco BBVA Cta 445000581 - 111005001
**Total:** 56,836,519 (Cuenta: 24360801)

| Concepto | Valor |
| :--- | :--- |
| Compra de Combustibles y derivados del petróleo | 6,000.00 |
| Compras Generales (declarantes) | 8,521,322.00 |
| Compras Generales (no declarantes) | 71,575.00 |
| Estampilla Adulto Mayor | 18,002,663.00 |
| Estampilla Pro Universidad de Nariño | 17,808.00 |
| Estampilla Procultura 1% | 1,382,063.00 |
| Estampilla Procultura 2% | 8,827,832.00 |
| Estampilla Prodeporte | 4,515,073.00 |
| Estampilla Proudenar | 2,942,041.00 |
| Honorarios Declarantes | 1,540,000.00 |
| Honorarios y Comisiones (No Declarantes) | 1,952,000.00 |
| Impuesto a las Ventas Retenido IVA 15% DECLARANTES | 3,297,945.52 |
| RETEICA SERVICIOS 10 X 1000 | 4,003,401.00 |
| Servicios Generales (Declarantes) | 979,495.00 |
| Servicios Generales (No Declarantes) | 777,300.00 |

### Banco AV VILLAS CTA 205017981 APS - 111005020
**Total:** 2,254,340

| Concepto | Valor |
| :--- | :--- |
| Compras Generales (declarantes) | 368,960.00 |
| Estampilla Adulto Mayor | 702,499.00 |
| Estampilla Procultura 2% | 351,250.00 |
| Estampilla Prodeporte | 175,625.00 |
| Estampilla Proudenar | 87,812.00 |
| Impuesto a las Ventas Retenido IVA 15% DECLARANTES | 420,610.00 |
| RETEICA SERVICIOS 10 X 1000 | 147,584.00 |

### Otras cuentas
*(Misma estructura a las anteriores)*

## Lógica del proceso
1. Consultar los pagos realizados en el período seleccionado.
2. Para cada pago, identificar la causación/cuenta por pagar asociada.
3. Consultar los descuentos registrados en dicha causación.
4. Determinar si el pago corresponde a (Tener en cuenta la participación del pago frente al total del valor causado, como referencia se debería tomar el valor de `pto_cop_detalle` y `pto_pag_detalle`):
   * **Pago único:** se toman el 100 % de los descuentos de la causación.
   * **Pago parcial:** se calcula y registra únicamente la parte proporcional de cada descuento correspondiente al valor del pago.
   * **Último pago:** se deberá determinar el saldo pendiente de los descuentos y asignarlo al último pago por diferencia, garantizando que no existan diferencias por redondeos.
5. Asociar los descuentos determinados a la cuenta bancaria/fuente de recursos utilizada en el pago.
6. Consolidar los valores por cuenta bancaria y concepto de descuento de acuerdo al nombre y cuenta registrado en libaux son todos los valores credito que no tienen valor en el campo ref, manteniendo la estructura actual del formato presentado anteriormente.

## Regla para el último pago
Cuando el sistema determine que el pago corresponde al último pago de la obligación, los descuentos deberán calcularse por diferencia:
> Descuento último pago = Descuento causado − descuentos asignados a pagos anteriores

Esto garantiza que la suma de los descuentos distribuidos entre todos los pagos sea exactamente igual a los descuentos registrados originalmente en la causación.

## Control principal
El reporte deberá garantizar que una misma causación no genere nuevamente el 100 % de sus descuentos en cada pago parcial, evitando así la duplicidad e inflación de los valores reportados.
