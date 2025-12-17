<?php

namespace App\DocumentoElectronico;

/**
 * Builder para construcción de documentos electrónicos
 * Utiliza el patrón Builder para crear estructuras de documentos complejas
 */
class DocumentBuilder
{
    private $document = [];
    private $items = [];
    private $taxes = [];
    private $allowances = [];

    /**
     * Establece el tipo de documento
     * @param string $type Tipo (Invoice, ReverseInvoice, CreditNote, DebitNote)
     * @return self
     */
    public function setDocumentType(string $type): self
    {
        $this->document['wdocumenttype'] = $type;
        return $this;
    }

    /**
     * Establece información básica del documento
     * @param array $info Array con datos básicos
     * @return self
     */
    public function setBasicInfo(array $info): self
    {
        $defaults = [
            'wdocumentsubtype' => '9',
            'wpaymentmeans' => '1',
            'wpaymentmethod' => 'ZZZ',
        ];

        $this->document = array_merge($this->document, $defaults, $info);
        return $this;
    }

    /**
     * Establece el prefijo y consecutivo
     * @param string $prefix Prefijo
     * @param int $consecutive Consecutivo
     * @return self
     */
    public function setReference(string $prefix, int $consecutive): self
    {
        $this->document['sauthorizationprefix'] = $prefix;
        $this->document['sdocumentsuffix'] = $consecutive;
        return $this;
    }

    /**
     * Establece las fechas del documento
     * @param string $issueDate Fecha de emisión (Y-m-d)
     * @param string $dueDate Fecha de vencimiento (Y-m-d)
     * @return self
     */
    public function setDates(string $issueDate, string $dueDate): self
    {
        $time = date('H:i:s', strtotime('-5 hour'));
        $this->document['tissuedate'] = $issueDate . 'T' . $time;
        $this->document['tduedate'] = $dueDate;
        return $this;
    }

    /**
     * Agrega información del comprador
     * @param array $buyer Datos del comprador
     * @return self
     */
    public function setBuyer(array $buyer): self
    {
        $this->document['jbuyer'] = $this->buildPartyInfo($buyer, 'buyer');
        return $this;
    }

    /**
     * Agrega información del vendedor
     * @param array $seller Datos del vendedor
     * @return self
     */
    public function setSeller(array $seller): self
    {
        $this->document['jseller'] = $this->buildPartyInfo($seller, 'seller');
        return $this;
    }

    /**
     * Agrega un ítem al documento
     * @param array $item Datos del ítem
     * @return self
     */
    public function addItem(array $item): self
    {
        $documentItem = [
            "sstandarditemidentification" => $item['codigo'] ?? '',
            "scustomname" => $item['detalle'] ?? $item['sdescription'] ?? '',
            "nusertotal" => floatval($item['val_unitario'] ?? 0) * floatval($item['cantidad'] ?? 1),
            "nprice" => floatval($item['val_unitario'] ?? 0),
            "icount" => intval($item['cantidad'] ?? 1),
        ];

        // Para facturas de venta se usan nombres diferentes
        if (isset($item['nunitprice'])) {
            $documentItem = [
                "sstandarditemidentification" => $item['codigo'] ?? '',
                "sdescription" => $item['detalle'] ?? '',
                "nunitprice" => floatval($item['val_unitario'] ?? 0),
                "ntotal" => floatval($item['val_unitario'] ?? 0) * floatval($item['cantidad'] ?? 1),
                "nquantity" => intval($item['cantidad'] ?? 1),
            ];
        }

        // Agregar IVA si existe
        if (isset($item['p_iva']) && $item['p_iva'] > 0) {
            $documentItem['jtax']['jiva'] = [
                "nrate" => floatval($item['p_iva']),
                "sname" => "IVA",
                "namount" => floatval($item['val_iva'] ?? 0),
                "nbaseamount" => floatval($item['val_unitario']) * intval($item['cantidad'])
            ];
        }

        // Agregar descuento si existe
        if (isset($item['p_dcto']) && $item['p_dcto'] > 0) {
            $documentItem['aallowancecharge']['1'] = [
                "nrate" => floatval($item['p_dcto']) * (-1),
                "scode" => "00",
                "namount" => floatval($item['val_dcto']) * (-1),
                "nbaseamont" => floatval($item['val_unitario']) * intval($item['cantidad']),
                "sreason" => $item['descuento_razon'] ?? "Descuento parcial"
            ];
        }

        $this->items[] = $documentItem;
        return $this;
    }

    /**
     * Agrega un impuesto a nivel de documento
     * @param string $type Tipo (jiva, jreterenta, jreteiva)
     * @param float $rate Porcentaje
     * @param float $amount Valor
     * @param float $baseAmount Base gravable
     * @return self
     */
    public function addTax(string $type, float $rate, float $amount, float $baseAmount): self
    {
        if ($rate > 0 && $amount > 0) {
            // Determinar nombre del impuesto
            switch ($type) {
                case 'jiva':
                    $taxName = 'IVA';
                    break;
                case 'jreterenta':
                    $taxName = 'ReteRenta';
                    break;
                case 'jreteiva':
                    $taxName = 'ReteIVA';
                    break;
                default:
                    $taxName = 'Tax';
                    break;
            }

            $this->taxes[$type] = [
                "sname" => $taxName,
                "nrate" => $rate,
                "namount" => $amount,
                "nbaseamount" => $baseAmount > 0 ? $baseAmount : ($amount * 100 / $rate)
            ];
        }
        return $this;
    }

    /**
     * Agrega un descuento a nivel de documento
     * @param float $rate Porcentaje
     * @param float $amount Valor
     * @param float $baseAmount Base
     * @param string $reason Razón
     * @return self
     */
    public function addAllowance(float $rate, float $amount, float $baseAmount, string $reason = 'Descuento General'): self
    {
        if ($rate > 0 && $amount > 0) {
            $this->allowances['1'] = [
                "nrate" => $rate * (-1),
                "scode" => "01",
                "namount" => $amount * (-1),
                "nbaseamont" => $baseAmount,
                "sreason" => $reason
            ];
        }
        return $this;
    }

    /**
     * Agrega notas al documento
     * @param string $notes Notas generales
     * @param string $topNote Nota superior (opcional)
     * @return self
     */
    public function setNotes(string $notes, string $topNote = ''): self
    {
        if (!empty($notes)) {
            $this->document['snotes'] = $notes;
        }
        if (!empty($topNote)) {
            $this->document['snotetop'] = $topNote;
        }
        return $this;
    }

    /**
     * Establece información adicional para documento soporte
     * @param array $extraInfo Información extra
     * @return self
     */
    public function setExtraInfo(array $extraInfo): self
    {
        foreach ($extraInfo as $key => $value) {
            $this->document[$key] = $value;
        }
        return $this;
    }

    /**
     * Construye la información de una parte (comprador/vendedor)
     * @param array $party Datos de la parte
     * @param string $type Tipo (buyer/seller)
     * @return array Estructura formateada
     */
    private function buildPartyInfo(array $party, string $type): array
    {
        $info = [
            'wlegalorganizationtype' => ($party['tipo_org'] ?? 1) == 1 ? 'person' : 'company',
            'stributaryidentificationkey' => 'ZZ',
            'stributaryidentificationname' => 'No Aplica',
        ];

        // Campos específicos para cada tipo
        if ($type === 'buyer') {
            $info['staxlevelcode'] = $party['resp_fiscal'] ?? 'R-99-PN';
            $info['sfiscalregime'] = ($party['reg_fiscal'] ?? 2) == 1 ? '49' : '48';

            $info['jpartylegalentity'] = [
                'wdoctype' => 'NIT',
                'sdocno' => $party['nit'] ?? '',
                'scorporateregistrationschemename' => $party['nombre'] ?? '',
            ];

            $info['jcontact'] = [
                'scontactperson' => $party['nombre'] ?? '',
                'selectronicmail' => $party['correo'] ?? '',
                'stelephone' => $party['telefono'] ?? '',
                'jregistrationaddress' => $this->buildAddress($party),
            ];
        } else if ($type === 'seller') {
            $info['staxlevelcode'] = $party['resp_fiscal'] ?? 'R-99-PN';
            $info['sfiscalresponsibilities'] = $party['resp_fiscal'] ?? 'R-99-PN';
            $info['sfiscalregime'] = ($party['reg_fiscal'] ?? 1) == 1 ? '49' : '48';

            // Para documento soporte (ReverseInvoice)
            if (isset($party['formato_soporte']) && $party['formato_soporte']) {
                $info['sdoctype'] = 'NIT';
                $info['sdocid'] = $party['no_doc'] ?? $party['nit'] ?? '';
                $info['ssellername'] = $party['nombre'] ?? '';
                $info['scontactperson'] = $party['nombre'] ?? '';
                $info['semail'] = $party['correo'] ?? '';
                $info['sphone'] = $party['telefono'] ?? '';
                $info['saddressline1'] = $party['direccion'] ?? '';
                $info['saddresszip'] = $party['cod_postal'] ?? '';
                $info['wdepartmentcode'] = $party['codigo_dpto'] ?? '';
                $info['sDepartmentName'] = ucfirst(mb_strtolower($party['nom_departamento'] ?? ''));
                $info['wtowncode'] = ($party['codigo_dpto'] ?? '') . ($party['codigo_municipio'] ?? '');
                $info['scityname'] = ucfirst(mb_strtolower($party['nom_municipio'] ?? ''));
            } else {
                // Para factura de venta
                $info['jpartylegalentity'] = [
                    'wdoctype' => 'NIT',
                    'sdocno' => $party['nit'] ?? '',
                    'scorporateregistrationschemename' => $party['nombre'] ?? '',
                ];

                $info['jtaxrepresentativeparty'] = [
                    'wdoctype' => 'NIT',
                    'sdocno' => $party['nit'] ?? '',
                ];

                $info['apartytaxschemes'] = [
                    '1' => [
                        'wdoctype' => 'NIT',
                        'sdocno' => $party['nit'] ?? '',
                        'spartyname' => $party['nombre'] ?? '',
                        'sregistrationname' => $party['nombre'] ?? '',
                    ]
                ];

                $info['jcontact'] = [
                    'selectronicmail' => $party['correo'] ?? '',
                    'stelephone' => $party['telefono'] ?? '',
                    'jregistrationaddress' => $this->buildAddress($party),
                    'jphysicallocationaddress' => $this->buildAddress($party, true),
                ];
            }
        }

        return $info;
    }

    /**
     * Construye la estructura de dirección
     * @param array $data Datos de ubicación
     * @param bool $physical Si es dirección física (incluye language)
     * @return array Estructura de dirección
     */
    private function buildAddress(array $data, bool $physical = false): array
    {
        $address = [
            'scountrycode' => $data['codigo_pais'] ?? 'CO',
            'wdepartmentcode' => $data['codigo_dpto'] ?? '',
            'wtowncode' => ($data['codigo_dpto'] ?? '') . ($data['codigo_municipio'] ?? ''),
            'scityname' => ucfirst(mb_strtolower($data['nom_municipio'] ?? '')),
            'saddressline1' => $data['direccion'] ?? '',
            'szip' => $data['cod_postal'] ?? '',
        ];

        // Campos adicionales según tipo de documento
        if (isset($data['wprovincecode'])) {
            $address['wprovincecode'] = $data['wprovincecode'];
            $address['sdepartmentname'] = ucfirst(mb_strtolower($data['nom_departamento'] ?? $data['nombre_dpto'] ?? ''));
            $address['sprovincename'] = ucfirst(mb_strtolower($data['nom_departamento'] ?? $data['nombre_dpto'] ?? ''));
        }

        if ($physical) {
            $address['wlanguage'] = 'es';
        }

        return $address;
    }

    /**
     * Construye y retorna el documento completo
     * @return array Documento listo para enviar
     */
    public function build(): array
    {
        // Agregar ítems
        $this->document['adocumentitems'] = $this->items;

        // Agregar impuestos si existen
        if (!empty($this->taxes)) {
            $this->document['jtax'] = $this->taxes;
        }

        // Agregar descuentos si existen
        if (!empty($this->allowances)) {
            $this->document['aallowancecharge'] = $this->allowances;
        }

        return $this->document;
    }

    /**
     * Reinicia el builder
     * @return self
     */
    public function reset(): self
    {
        $this->document = [];
        $this->items = [];
        $this->taxes = [];
        $this->allowances = [];
        return $this;
    }
}
