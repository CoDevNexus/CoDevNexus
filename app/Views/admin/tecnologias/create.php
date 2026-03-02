<?php use Core\Security; ?>

<div class="page-header">
  <h2>🔧 Nueva Tecnología</h2>
  <a href="/admin/tecnologias" class="btn btn-secondary">← Volver</a>
</div>

<form method="POST" action="/admin/tecnologias/store" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="form-row">
    <div class="form-group">
      <label>Nombre *</label>
      <input type="text" name="nombre" required maxlength="80">
    </div>
    <div class="form-group">
      <label>Categoría</label>
      <select name="categoria">
        <?php foreach (['lenguaje','framework','base_datos','red','devops','iot','otro'] as $c): ?>
          <option value="<?= $c ?>"><?= ucfirst(str_replace('_',' ',$c)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Nivel (%)</label>
      <input type="number" name="nivel" value="75" min="0" max="100">
    </div>
  </div>

<?php
  $icoTipo = 'devicon';
  $icoCls  = '';
  $icoSvg  = '';
  require __DIR__ . '/_icon_picker.php';
?>

  <div class="form-row">
    <div class="form-group form-check">
      <label><input type="checkbox" name="visible" value="1" checked> Visible</label>
    </div>
    <div class="form-group">
      <label>Orden</label>
      <input type="number" name="orden" value="0" min="0">
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Guardar Tecnología</button>
</form>


