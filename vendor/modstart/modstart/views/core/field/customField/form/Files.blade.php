<div>
    <?php
    $f = new \ModStart\Field\Files($fieldName, [$field['title']]);
    if (!empty($field['isRequired'])) {
        $f->required();
    }
    $f->renderMode(\ModStart\Field\Type\FieldRenderMode::FORM);
    $f->value(!empty($record)?$record[$fieldName]:null);
    if(!empty($param['fileServer'])){
        $f->server($param['fileServer']);
    }
    echo $f->render();
    ?>
</div>