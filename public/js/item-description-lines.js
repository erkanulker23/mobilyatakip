(function (global) {
    'use strict';

    function cleanLine(text) {
        return String(text || '')
            .replace(/^[-*•·]\s+/, '')
            .replace(/^\d+[.)]\s+/, '')
            .trim();
    }

    function linesFromText(text) {
        if (!text || !String(text).trim()) {
            return [''];
        }
        var lines = [];
        String(text).replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n').forEach(function (part) {
            var line = cleanLine(part);
            if (line) {
                lines.push(line);
            }
        });
        return lines.length ? lines : [''];
    }

    function linesFromItem(item) {
        if (!item) {
            return [''];
        }
        if (Array.isArray(item.descriptionLines) && item.descriptionLines.length) {
            var fromArray = item.descriptionLines.map(cleanLine).filter(Boolean);
            return fromArray.length ? fromArray : [''];
        }
        return linesFromText(item.description || '');
    }

    function getRowIdx(block) {
        var row = block.closest('.item-row');
        return row ? row.getAttribute('data-row-idx') : null;
    }

    function lineInputName(rowIdx) {
        return 'items[' + rowIdx + '][descriptionLines][]';
    }

    function bindLine(lineEl, block) {
        var removeBtn = lineEl.querySelector('.item-desc-remove');
        if (!removeBtn || removeBtn.dataset.bound === '1') {
            return;
        }
        removeBtn.dataset.bound = '1';
        removeBtn.addEventListener('click', function () {
            var lines = block.querySelector('.item-desc-lines');
            var all = lines ? lines.querySelectorAll('.item-desc-line') : [];
            if (all.length <= 1) {
                var inp = lineEl.querySelector('.item-desc-line-input');
                if (inp) {
                    inp.value = '';
                }
                return;
            }
            lineEl.remove();
        });
    }

    function addLine(block, value, rowIdx) {
        var tmpl = document.getElementById('item-desc-line-template');
        var lines = block.querySelector('.item-desc-lines');
        if (!tmpl || !lines) {
            return null;
        }
        var clone = tmpl.content.cloneNode(true);
        var input = clone.querySelector('.item-desc-line-input');
        if (input) {
            if (rowIdx !== null && rowIdx !== undefined) {
                input.name = lineInputName(rowIdx);
            }
            if (value) {
                input.value = value;
            }
        }
        lines.appendChild(clone);
        var appended = lines.lastElementChild;
        if (appended) {
            bindLine(appended, block);
        }
        return appended;
    }

    function clearLines(block) {
        var lines = block.querySelector('.item-desc-lines');
        if (lines) {
            lines.innerHTML = '';
        }
    }

    function bindAddButton(block) {
        var addBtn = block.querySelector('.item-desc-add');
        if (!addBtn || addBtn.dataset.bound === '1') {
            return;
        }
        addBtn.dataset.bound = '1';
        addBtn.addEventListener('click', function () {
            var line = addLine(block, '', getRowIdx(block));
            var inp = line && line.querySelector('.item-desc-line-input');
            if (inp) {
                inp.focus();
            }
        });
    }

    function initBlock(block, initialItem) {
        if (!block) {
            return;
        }

        var hasInitial = initialItem !== undefined && initialItem !== null;
        if (hasInitial) {
            clearLines(block);
            var rowIdx = getRowIdx(block);
            linesFromItem(initialItem).forEach(function (val) {
                addLine(block, val, rowIdx);
            });
        } else if (block.dataset.descInit !== '1') {
            block.querySelectorAll('.item-desc-line').forEach(function (lineEl) {
                bindLine(lineEl, block);
            });
            if (!block.querySelector('.item-desc-line')) {
                addLine(block, '', getRowIdx(block));
            }
        }

        bindAddButton(block);
        block.dataset.descInit = '1';
    }

    function initRow(rowEl, initialItem) {
        var block = rowEl ? rowEl.querySelector('.item-desc-block') : null;
        if (block) {
            initBlock(block, initialItem);
        }
    }

    function duplicateLines(srcRow, destRow) {
        var srcBlock = srcRow.querySelector('.item-desc-block');
        var destBlock = destRow.querySelector('.item-desc-block');
        if (!srcBlock || !destBlock) {
            return;
        }
        var values = [];
        srcBlock.querySelectorAll('.item-desc-line-input').forEach(function (inp) {
            var v = inp.value.trim();
            if (v) {
                values.push(v);
            }
        });
        if (!values.length) {
            values = [''];
        }
        clearLines(destBlock);
        destBlock.dataset.descInit = '0';
        initBlock(destBlock, { descriptionLines: values });
    }

    global.ItemDescriptionLines = {
        initBlock: initBlock,
        initRow: initRow,
        duplicateLines: duplicateLines,
        linesFromItem: linesFromItem,
    };
})(window);
