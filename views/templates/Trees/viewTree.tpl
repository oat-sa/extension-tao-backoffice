<div class="main-container">
    <h2 class="panel"><?= __('View tree') ?></h2>

    <?php
    $class= new core_kernel_classes_Class(get_data('id'));
    foreach ($class->getInstances(false)as $node) :?>
        <a class="browseLink" href="<?= $node->getUri() ?>"><?= $node->getLabel() ?></a><br/>
    <?php endforeach; ?>
    <div class="tree-container" data-id="<?= get_data('id') ?>">

    
    </div>
</div>

