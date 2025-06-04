<?php
// conexao.php
$usuario = 'SEU_USUARIO';
$senha = 'SUA_SENHA';
$tns = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SID=XE)))";

$conn = oci_connect($usuario, $senha, $tns);
if (!$conn) {
    $erro = oci_error();
    echo "Erro de conexão: " . $erro['message'];
    exit;
}
?>

<!-- listar_tarefas.php -->
<?php
include 'conexao.php';

session_start();
$id_usuario = $_SESSION['id_usuario'] ?? 1; // Substituir conforme sistema de login

$sql = "SELECT t.id_tarefa, t.titulo, t.texto, TO_CHAR(t.data_inicio, 'DD/MM/YYYY') AS data_inicio,
               TO_CHAR(t.data_final, 'DD/MM/YYYY') AS data_final,
               tp.nome AS tipo_nome, tp.cor, tp.icone
        FROM tarefa t
        JOIN tipo tp ON t.id_tipo = tp.id_tipo
        WHERE t.id_usuario = :id_usuario
        ORDER BY t.data_final ASC";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id_usuario", $id_usuario);
oci_execute($stid);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Minhas Tarefas</title>
  <style>
    body { font-family: Arial; padding: 20px; background: #f4f4f4; }
    .tarefa {
      background: white;
      border-left: 10px solid;
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .titulo { font-weight: bold; font-size: 18px; }
    .tipo { font-size: 14px; color: #666; }
    .datas { font-size: 13px; color: #888; }
  </style>
</head>
<body>

<h2>Minhas Tarefas</h2>
<a href="form_inserir_tarefa.php">Adicionar Nova Tarefa</a>
<?php while ($row = oci_fetch_assoc($stid)): ?>
  <div class="tarefa" style="border-color: <?= htmlspecialchars($row['COR']) ?>;">
    <div class="titulo"><?= htmlspecialchars($row['ICONE'] . ' ' . $row['TITULO']) ?></div>
    <div class="texto"><?= htmlspecialchars($row['TEXTO']) ?></div>
    <div class="tipo">Tipo: <?= htmlspecialchars($row['TIPO_NOME']) ?></div>
    <div class="datas">De <?= $row['DATA_INICIO'] ?> até <?= $row['DATA_FINAL'] ?></div>
    <a href="editar_tarefa.php?id=<?= $row['ID_TAREFA'] ?>">✏️ Editar</a> |
    <a href="excluir_tarefa.php?id=<?= $row['ID_TAREFA'] ?>" onclick="return confirm('Tem certeza que deseja excluir?')">❌ Excluir</a>
  </div>
<?php endwhile; ?>

<?php
oci_free_statement($stid);
oci_close($conn);
?>
</body>
</html>

<!-- form_inserir_tarefa.php -->
<form action="inserir_tarefa.php" method="post">
  <input type="text" name="titulo" placeholder="Título" required><br>
  <textarea name="texto" placeholder="Descrição" required></textarea><br>
  <input type="date" name="data_inicio" required><br>
  <input type="date" name="data_final" required><br>
  <select name="id_tipo">
    <option value="1">Urgente</option>
    <option value="2">Estudo</option>
    <option value="3">Concluído</option>
    <option value="4">Pendente</option>
    <option value="5">Manutenção</option>
  </select><br>
  <input type="hidden" name="id_usuario" value="<?= $_SESSION['id_usuario'] ?? 1 ?>">
  <button type="submit">Adicionar Tarefa</button>
</form>

<!-- inserir_tarefa.php -->
<?php
include 'conexao.php';

$titulo = $_POST['titulo'];
$texto = $_POST['texto'];
$data_inicio = $_POST['data_inicio'];
$data_final = $_POST['data_final'];
$id_tipo = $_POST['id_tipo'];
$id_usuario = $_POST['id_usuario'];

$sql = "INSERT INTO tarefa (id_usuario, titulo, texto, data_inicio, data_final, id_tipo)
        VALUES (:id_usuario, :titulo, :texto, TO_DATE(:data_inicio, 'YYYY-MM-DD'), TO_DATE(:data_final, 'YYYY-MM-DD'), :id_tipo)";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id_usuario", $id_usuario);
oci_bind_by_name($stmt, ":titulo", $titulo);
oci_bind_by_name($stmt, ":texto", $texto);
oci_bind_by_name($stmt, ":data_inicio", $data_inicio);
oci_bind_by_name($stmt, ":data_final", $data_final);
oci_bind_by_name($stmt, ":id_tipo", $id_tipo);

$result = oci_execute($stmt);

if ($result) {
    echo "Tarefa adicionada com sucesso!";
} else {
    $e = oci_error($stmt);
    echo "Erro ao inserir tarefa: " . $e['message'];
}
?>
<a href="listar_tarefas.php">Voltar</a>

<!-- editar_tarefa.php -->
<?php
include 'conexao.php';
$id = $_GET['id'];
$sql = "SELECT * FROM tarefa WHERE id_tarefa = :id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id", $id);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
?>
<h2>Editar Tarefa</h2>
<form action="salvar_edicao.php" method="post">
  <input type="hidden" name="id_tarefa" value="<?= $row['ID_TAREFA'] ?>">
  <input type="text" name="titulo" value="<?= $row['TITULO'] ?>" required><br>
  <textarea name="texto" required><?= $row['TEXTO'] ?></textarea><br>
  <input type="date" name="data_inicio" value="<?= date('Y-m-d', strtotime($row['DATA_INICIO'])) ?>" required><br>
  <input type="date" name="data_final" value="<?= date('Y-m-d', strtotime($row['DATA_FINAL'])) ?>" required><br>
  <input type="number" name="id_tipo" value="<?= $row['ID_TIPO'] ?>" required><br>
  <button type="submit">Salvar</button>
</form>

<!-- salvar_edicao.php -->
<?php
include 'conexao.php';

$id = $_POST['id_tarefa'];
$titulo = $_POST['titulo'];
$texto = $_POST['texto'];
$data_inicio = $_POST['data_inicio'];
$data_final = $_POST['data_final'];
$id_tipo = $_POST['id_tipo'];

$sql = "UPDATE tarefa
        SET titulo = :titulo,
            texto = :texto,
            data_inicio = TO_DATE(:data_inicio, 'YYYY-MM-DD'),
            data_final = TO_DATE(:data_final, 'YYYY-MM-DD'),
            id_tipo = :id_tipo
        WHERE id_tarefa = :id";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":titulo", $titulo);
oci_bind_by_name($stmt, ":texto", $texto);
oci_bind_by_name($stmt, ":data_inicio", $data_inicio);
oci_bind_by_name($stmt, ":data_final", $data_final);
oci_bind_by_name($stmt, ":id_tipo", $id_tipo);
oci_bind_by_name($stmt, ":id", $id);

if (oci_execute($stmt)) {
    echo "Tarefa atualizada com sucesso!";
} else {
    $e = oci_error($stmt);
    echo "Erro: " . $e['message'];
}
?>
<a href="listar_tarefas.php">Voltar</a>

<!-- excluir_tarefa.php -->
<?php
include 'conexao.php';

$id = $_GET['id'];
$sql = "DELETE FROM tarefa WHERE id_tarefa = :id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id", $id);

if (oci_execute($stmt)) {
    echo "Tarefa excluída com sucesso!";
} else {
    $e = oci_error($stmt);
    echo "Erro ao excluir: " . $e['message'];
}
?>
<a href="listar_tarefas.php">Voltar</a>
