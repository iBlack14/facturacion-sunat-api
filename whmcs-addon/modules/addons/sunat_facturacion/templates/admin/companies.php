<?php
use WHMCS\Database\Capsule;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_company') {
            // Agregar nueva empresa
            Capsule::table('mod_sunat_companies')->insert([
                'company_name' => $_POST['company_name'],
                'api_url' => $_POST['api_url'],
                'api_email' => $_POST['api_email'],
                'api_password' => encrypt($_POST['api_password']),
                'company_id' => $_POST['company_id'],
                'branch_id' => $_POST['branch_id'],
                'modo' => $_POST['modo'],
                'serie_factura' => $_POST['serie_factura'],
                'serie_boleta' => $_POST['serie_boleta'],
                'auto_emit' => isset($_POST['auto_emit']) ? 1 : 0,
                'emit_on_unpaid' => isset($_POST['emit_on_unpaid']) ? 1 : 0,
                'active' => isset($_POST['active']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            echo '<div class="alert alert-success">Empresa agregada exitosamente</div>';
        } elseif ($_POST['action'] === 'edit_company') {
            // Editar empresa
            $updateData = [
                'company_name' => $_POST['company_name'],
                'api_url' => $_POST['api_url'],
                'api_email' => $_POST['api_email'],
                'company_id' => $_POST['company_id'],
                'branch_id' => $_POST['branch_id'],
                'modo' => $_POST['modo'],
                'serie_factura' => $_POST['serie_factura'],
                'serie_boleta' => $_POST['serie_boleta'],
                'auto_emit' => isset($_POST['auto_emit']) ? 1 : 0,
                'emit_on_unpaid' => isset($_POST['emit_on_unpaid']) ? 1 : 0,
                'active' => isset($_POST['active']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!empty($_POST['api_password'])) {
                $updateData['api_password'] = encrypt($_POST['api_password']);
            }

            Capsule::table('mod_sunat_companies')
                ->where('id', $_POST['company_edit_id'])
                ->update($updateData);

            echo '<div class="alert alert-success">Empresa actualizada exitosamente</div>';
        }
    }
}

// Obtener empresas
$companies = Capsule::table('mod_sunat_companies')->orderBy('id', 'desc')->get();
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">Empresas SUNAT Configuradas</h3>
    </div>
    <div class="panel-body">
        <button class="btn btn-success" data-toggle="modal" data-target="#modalAddCompany">
            <i class="fa fa-plus"></i> Nueva Empresa
        </button>

        <hr>

        <?php if (count($companies) > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>API URL</th>
                        <th>Modo</th>
                        <th>Series</th>
                        <th>Automático</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?php echo $company->id; ?></td>
                            <td><strong><?php echo $company->company_name; ?></strong></td>
                            <td><small><?php echo $company->api_url; ?></small></td>
                            <td>
                                <span class="label label-<?php echo $company->modo == 'produccion' ? 'danger' : 'warning'; ?>">
                                    <?php echo strtoupper($company->modo); ?>
                                </span>
                            </td>
                            <td>
                                F: <?php echo $company->serie_factura; ?><br>
                                B: <?php echo $company->serie_boleta; ?>
                            </td>
                            <td>
                                <?php echo $company->auto_emit ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'; ?>
                            </td>
                            <td>
                                <span class="label label-<?php echo $company->active ? 'success' : 'default'; ?>">
                                    <?php echo $company->active ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-primary" onclick="editCompany(<?php echo htmlspecialchars(json_encode($company)); ?>)">
                                    <i class="fa fa-edit"></i> Editar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">
                No hay empresas configuradas. Haz clic en "Nueva Empresa" para agregar una.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Agregar Empresa -->
<div class="modal fade" id="modalAddCompany" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add_company">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Agregar Nueva Empresa SUNAT</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre Identificador *</label>
                                <input type="text" name="company_name" class="form-control" required>
                                <small class="help-block">Ej: BLXKSTUDIO, Mi Empresa SAC</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>URL de la API *</label>
                                <input type="url" name="api_url" class="form-control" value="https://api-sunat.blxkstudio.com" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email API *</label>
                                <input type="email" name="api_email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password API *</label>
                                <input type="password" name="api_password" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Company ID (en API) *</label>
                                <input type="number" name="company_id" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Branch ID (en API) *</label>
                                <input type="number" name="branch_id" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Modo SUNAT *</label>
                                <select name="modo" class="form-control" required>
                                    <option value="beta">Beta (Pruebas)</option>
                                    <option value="produccion">Producción</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Serie Facturas *</label>
                                <input type="text" name="serie_factura" class="form-control" value="F001" maxlength="4" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Serie Boletas *</label>
                                <input type="text" name="serie_boleta" class="form-control" value="B001" maxlength="4" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="auto_emit" checked> Emisión Automática
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="emit_on_unpaid" checked> Emitir en Unpaid
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="active" checked> Activo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Empresa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Empresa -->
<div class="modal fade" id="modalEditCompany" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="formEditCompany">
                <input type="hidden" name="action" value="edit_company">
                <input type="hidden" name="company_edit_id" id="company_edit_id">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Editar Empresa SUNAT</h4>
                </div>
                <div class="modal-body">
                    <!-- Mismo contenido que modal add pero con id="edit_*" -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre Identificador *</label>
                                <input type="text" name="company_name" id="edit_company_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>URL de la API *</label>
                                <input type="url" name="api_url" id="edit_api_url" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email API *</label>
                                <input type="email" name="api_email" id="edit_api_email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password API</label>
                                <input type="password" name="api_password" id="edit_api_password" class="form-control">
                                <small class="help-block">Dejar vacío para mantener actual</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Company ID *</label>
                                <input type="number" name="company_id" id="edit_company_id" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Branch ID *</label>
                                <input type="number" name="branch_id" id="edit_branch_id" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Modo SUNAT *</label>
                                <select name="modo" id="edit_modo" class="form-control" required>
                                    <option value="beta">Beta</option>
                                    <option value="produccion">Producción</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Serie Facturas *</label>
                                <input type="text" name="serie_factura" id="edit_serie_factura" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Serie Boletas *</label>
                                <input type="text" name="serie_boleta" id="edit_serie_boleta" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="auto_emit" id="edit_auto_emit"> Emisión Automática
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="emit_on_unpaid" id="edit_emit_on_unpaid"> Emitir en Unpaid
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="active" id="edit_active"> Activo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Empresa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCompany(company) {
    $('#company_edit_id').val(company.id);
    $('#edit_company_name').val(company.company_name);
    $('#edit_api_url').val(company.api_url);
    $('#edit_api_email').val(company.api_email);
    $('#edit_company_id').val(company.company_id);
    $('#edit_branch_id').val(company.branch_id);
    $('#edit_modo').val(company.modo);
    $('#edit_serie_factura').val(company.serie_factura);
    $('#edit_serie_boleta').val(company.serie_boleta);
    $('#edit_auto_emit').prop('checked', company.auto_emit == 1);
    $('#edit_emit_on_unpaid').prop('checked', company.emit_on_unpaid == 1);
    $('#edit_active').prop('checked', company.active == 1);
    $('#modalEditCompany').modal('show');
}
</script>
