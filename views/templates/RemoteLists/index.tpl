<div class="main-container flex-container-main-form">
    <h2><?= __('Create a remote list') ?></h2>

    <div class="form-content">
        <?= get_data('form') ?>
    </div>
</div>

<div class="data-container-wrapper flex-container-remainder">
    <?php
        $reloadListTitle = __('Reload this list');
        $deleteListTitle = __('Delete this list');
    ?>
    <?php foreach (get_data('lists') as $list): ?>
        <section id="list-data_<?= $list['uri'] ?>" class="data-container list-container">
            <header class="container-title">
                <h6><?= _dh($list['label']) ?></h6>
            </header>
            <div class="container-content" id="list-elements_<?= $list['uri'] ?>">
                <ol>
                    <?php foreach ($list['elements'] as $level => $element): ?>
                        <li id="list-element_<?= $level ?>">
                            <span
                                class="list-element"
                                id="list-element_<?= $level ?>_<?= \tao_helpers_Uri::encode($element->getUri()) ?>"
                            ><?= _dh($element->getLabel()) ?></span>
                        </li>
                    <?php endforeach ?>
                    <?php $numberOfElements = count($list['elements']); ?>
                    <?php if ($list['totalCount'] > $numberOfElements): ?>
                        <li id="list-element_<?= ++$numberOfElements ?>">
                            <span class="list-element" id="list-element_<?= $numberOfElements ?>_>">...</span>
                        </li>
                    <?php endif ?>
                </ol>
            </div>
            <footer class="data-container-footer action-bar">
                <button
                    type="button"
                    title="<?= $reloadListTitle ?>"
                    class="icon-reload list-reload-btn btn-info small rgt"
                    data-uri="<?= $list['uri'] ?>"
                ></button>
                <?php if ($list['editable']): ?>
                <button
                    type="button"
                    title="<?= $deleteListTitle ?>"
                    class="icon-bin list-delete-btn btn-warning small rgt"
                    data-uri="<?= $list['uri'] ?>"
                ></button>
                <?php endif ?>
            </footer>
        </section>
    <?php endforeach ?>
</div>
