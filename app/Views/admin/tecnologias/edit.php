<?php use Core\Security; ?>

<div class="page-header">
  <h2>✏️ Editar Tecnología</h2>
  <a href="/admin/tecnologias" class="btn btn-secondary">← Volver</a>
</div>

<form method="POST" action="/admin/tecnologias/update/<?= (int)$tech['id'] ?>" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="form-row">
    <div class="form-group">
      <label>Nombre *</label>
      <input type="text" name="nombre" required maxlength="80" value="<?= Security::escape($tech['nombre']) ?>">
    </div>
    <div class="form-group">
      <label>Categoría</label>
      <select name="categoria">
        <?php foreach (['lenguaje','framework','base_datos','red','devops','iot','otro'] as $c): ?>
          <option value="<?= $c ?>" <?= $tech['categoria']===$c ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$c)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Nivel (%)</label>
      <input type="number" name="nivel" value="<?= (int)$tech['nivel'] ?>" min="0" max="100">
    </div>
  </div>

<?php
  $icoTipo = in_array($tech['icono_tipo'], ['devicon','ri','fab','fas','svg_custom'], true)
             ? $tech['icono_tipo']
             : 'devicon';
  // legacy: old rows used 'devicons' as the tipo value
  if ($icoTipo === 'devicons' || $tech['icono_tipo'] === 'devicons') $icoTipo = 'devicon';
  $icoCls  = $tech['icono_tipo'] !== 'svg_custom' ? ($tech['icono_valor'] ?? '') : '';
  $icoSvg  = $tech['icono_tipo'] === 'svg_custom'  ? ($tech['icono_valor'] ?? '') : '';
  require __DIR__ . '/_icon_picker.php';
?>

  <div class="form-row">
    <div class="form-group form-check">
      <label><input type="checkbox" name="visible" value="1" <?= $tech['visible'] ? 'checked' : '' ?>> Visible</label>
    </div>
    <div class="form-group">
      <label>Orden</label>
      <input type="number" name="orden" value="<?= (int)$tech['orden'] ?>" min="0">
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Actualizar Tecnología</button>
</form>


