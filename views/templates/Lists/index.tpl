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
    <input id="data-max-items" type="hidden" value="<?= $maxItems ;?>" data-testid="maxItems" />

    <?php
        $editListTitle = __('Edit this list');
        $deleteListTitle = __('Delete this list');
    ?>
    <?php foreach (get_data('lists') as $i => $list): ?>
    <section id="list-data_<?= $list['uri'] ?>" class="data-container list-container">
        <header class="container-title">
            <h6 data-testid="listName"><?= _dh($list['label']) ?></h6>
        </header>
        <div class="container-content" id="list-elements_<?= $list['uri'] ?>">
            <ol data-testid="elements">
                <?php foreach ($list['elements'] as $level => $element): ?>
                <li id="list-element_<?= $level ?>">
                    <span
                        class="list-element"
                        id="list-element_<?= $level ?>_<?= \tao_helpers_Uri::encode($element->getUri()) ?>"
                    ><?= _dh($element->getLabel()) ?></span>
                </li>
                <?php endforeach ?>
            </ol>
            <?php if ($list['totalCount'] > count($list['elements'])) : ?>
                <div class='pagination-container'>
                    <div class='load-more-btn' data-uri="<?= $list['uri'] ?>">
                        <span class="icon-loop"/>
                        <a>Load more</a>
                    </div>
                    <span><?= $list['totalCount'] ?> elements</span>
                </div>
            <?php endif ?>
        </div>
        <footer class="data-container-footer action-bar  <?php !$list['editable'] && print 'hidden'?>">
        <?php if ($list['editable']): ?>
            <button
                type="button"
                title="<?= $editListTitle ?>"
                class="icon-edit list-edit-btn btn-info small rgt"
                data-testid="listEditButton"
                data-uri="<?= $list['uri'] ?>"
            ></button>
            <button
                type="button"
                title="<?= $deleteListTitle ?>"
                class="icon-bin list-delete-btn btn-warning small rgt"
                data-testid="listDeleteButton"
                data-uri="<?= $list['uri'] ?>"
            ></button>
        <?php endif ?>
        </footer>
    </section>
    <?php endforeach ?>
</div>
