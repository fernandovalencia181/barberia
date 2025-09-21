<div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" placeholder="Nombre" 
        value="<?php echo $usuario->nombre; ?>">
</div>

<div class="campo">
    <label for="apellido">Apellido</label>
    <input type="text" id="apellido" name="apellido" placeholder="Apellido" 
        value="<?php echo $usuario->apellido; ?>">
</div>

<div class="campo">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="Email" value="<?php echo $usuario->email; ?>">
</div>

<div class="campo">
    <label for="telefono">Teléfono</label>
    <input type="text" id="telefono" name="telefono" placeholder="Teléfono" value="<?php echo $usuario->telefono; ?>">
</div>

<div class="campo">
    <label for="password">Password</label>
    <input type="password" id="password" placeholder="Password del barbero" name="password">
</div>

<div class="campo">
    <label for="password2">Repetir Password</label>
    <input type="password" id="password2" placeholder="Repetir Password del barbero" name="password2">
</div>

<div class="campo">
    <label for="imagen">Foto</label>
    <input class="seleccionar-archivo" type="file" id="imagen" name="imagen" accept="image/*">
    <?php if(!empty($usuario->imagen)): ?>
        <img src="/uploads/<?php echo $usuario->imagen; ?>" class="preview-img">
    <?php endif; ?>
</div>
