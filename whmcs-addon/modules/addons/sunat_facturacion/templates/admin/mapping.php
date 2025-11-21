<?php
use WHMCS\Database\Capsule;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_mapping') {
        Capsule::table('mod_sunat_product_mapping')->insert([
            'whmcs_product_id' => $_POST['whmcs_product_id'],
            'sunat_code' => $_POST['sunat_code'],
            'sunat_description' => $_POST['sunat_description'],
            'sunat_unit' => $_POST['sunat_unit'],
            'sunat_tip_afe_igv' => $_POST['sunat_tip_afe_igv'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        echo '<div class="alert alert-success">Mapeo agregado exitosamente</div>';
    } elseif ($_POST['action'] === 'delete_mapping') {
        Capsule::table('mod_sunat_product_mapping')->where('id', $_POST['mapping_id'])->delete();
        echo '<div class="alert alert-success">Mapeo eliminado</div>';
    }
}

// Obtener mapeos
$mappings = Capsule::table('mod_sunat_product_mapping as m')
    ->join('tblproducts as p', 'm.whmcs_product_id', '=', 'p.id')
    ->select('m.*', 'p.name as product_name')
    ->get();

// Obtener productos WHMCS sin mapeo
$mappedIds = $mappings->pluck('whmcs_product_id')->toArray();
$products = Capsule::table('tblproducts')
    ->whereNotIn('id', $mappedIds)
    ->where('hidden', 0)
    ->get();
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Mapeo de Productos WHMCS → SUNAT</h3>
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <strong><i class="fa fa-info-circle"></i> Información:</strong>
            Los productos sin mapeo usarán automáticamente su nombre como descripción y código genérico.
            Configura mapeos solo para productos que requieran códigos SUNAT específicos.
        </div>

        <button class="btn btn-success" data-toggle="modal" data-target="#modalAddMapping">
            <i class="fa fa-plus"></i> Nuevo Mapeo
        </button>

        <hr>

        <?php if (count($mappings) > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Producto WHMCS</th>
                        <th>Código SUNAT</th>
                        <th>Descripción SUNAT</th>
                        <th>Unidad</th>
                        <th>Afectación IGV</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mappings as $mapping): ?>
                        <tr>
                            <td><strong><?php echo $mapping->product_name; ?></strong></td>
                            <td><?php echo $mapping->sunat_code; ?></td>
                            <td><?php echo $mapping->sunat_description; ?></td>
                            <td><?php echo $mapping->sunat_unit; ?></td>
                            <td>
                                <?php
                                $afectacion = [
                                    '10' => 'Gravado',
                                    '20' => 'Exonerado',
                                    '30' => 'Inafecto'
                                ];
                                echo $afectacion[$mapping->sunat_tip_afe_igv] ?? $mapping->sunat_tip_afe_igv;
                                ?>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_mapping">
                                    <input type="hidden" name="mapping_id" value="<?php echo $mapping->id; ?>">
                                    <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar este mapeo?')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">
                No hay mapeos configurados. Los productos usarán sus nombres originales.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Agregar Mapeo -->
<div class="modal fade" id="modalAddMapping" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add_mapping">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Agregar Mapeo de Producto</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Producto WHMCS *</label>
                        <select name="whmcs_product_id" class="form-control" required>
                            <option value="">Seleccionar producto...</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product->id; ?>"><?php echo $product->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Código SUNAT *</label>
                        <input type="text" name="sunat_code" class="form-control" maxlength="20" required>
                        <small class="help-block">Ej: 81112200 (Servicios de hosting)</small>
                    </div>

                    <div class="form-group">
                        <label>Descripción para SUNAT *</label>
                        <input type="text" name="sunat_description" class="form-control" maxlength="255" required>
                    </div>

                    <div class="form-group">
                        <label>Unidad de Medida *</label>
                        <select name="sunat_unit" class="form-control" required>
                            <option value="NIU">NIU - Unidad</option>
                            <option value="ZZ">ZZ - Servicio</option>
                            <option value="KGM">KGM - Kilogramo</option>
                            <option value="MTR">MTR - Metro</option>
                            <option value="LTR">LTR - Litro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tipo de Afectación IGV *</label>
                        <select name="sunat_tip_afe_igv" class="form-control" required>
                            <option value="10">10 - Gravado (con IGV)</option>
                            <option value="20">20 - Exonerado</option>
                            <option value="30">30 - Inafecto</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Mapeo</button>
                </div>
            </form>
        </div>
    </div>
</div>
