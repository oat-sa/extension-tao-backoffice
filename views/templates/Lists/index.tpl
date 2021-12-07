<div class="main-container flex-container-full">
    <h2><?= __('Lists') ?></h2>
    <div class="create-list-wrapper">
        <div class="xhtml_form">
            <form id="createList" name="createList">
                <input type="hidden" class="global" name="createList_sent" value="1">
                <div class="form-toolbar">
                    <button type="button" class="form-submitter btn-success"><span class="icon-add"></span> Create list</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="data-container-wrapper flex-container-remainder">
    <?php
        $editListTitle = __('Edit this list');
        $deleteListTitle = __('Delete this list');
    ?>
    <?php foreach (get_data('lists') as $i => $list): ?>
    <section id="list-data_<?= $list['uri'] ?>" class="data-container list-container">
        <header class="container-title">
            <h6><?= _dh($list['label']) ?></h6>
        </header>
        <div class="container-content" id="list-elements_<?= $list['uri'] ?>">
            <ol>
                <?php foreach ($list['elements'] as $level => $element): ?>
                <li id="list-element_<?= $level ?>">
                    <span class="list-element" id="list-element_<?= $level ?>_<?= $element['uri'] ?>"><?= _dh($element['label']) ?></span>
                </li>
                <?php endforeach ?>
            </ol>
        </div>
        <footer class="data-container-footer action-bar  <?php !$list['editable'] && print 'hidden'?>">
        <?php if ($list['editable']): ?>
            <button type="button" title="<?= $editListTitle ?>" class="icon-edit list-edit-btn btn-info small rgt" data-uri="<?= $list['uri'] ?>">
            </button>
            <button type="button" title="<?= $deleteListTitle ?>" class="icon-bin list-delete-btn btn-warning small rgt" data-uri="<?= $list['uri'] ?>">
            </button>
        <?php endif ?>
        </footer>
    </section>
    <?php endforeach ?>
</div>
