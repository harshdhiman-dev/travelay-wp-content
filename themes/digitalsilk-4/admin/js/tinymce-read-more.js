(function () {

    tinymce.PluginManager.add('readmore_content', function (editor, url) {

        editor.addButton('readmore_content', {
            title: 'Add/Remove More Content',
            cmd: 'readmore_content',
            icon: 'pagebreak',
        });

        editor.addCommand('readmore_content', function () {
            editor.focus();

            const readMoreClass = "read-more-wrapper";
            const readMoreClassText = "read-more-text";
            const selectedText = tinymce.activeEditor.selection.getContent();
            const selectedNode = tinymce.activeEditor.selection.getNode();
            const closestReadMore = selectedNode.closest(`.${readMoreClass}`)
            const iframeElement = tinymce.activeEditor.selection.editor.iframeElement;
            const createReadMore = `<div  id=rm-${Math.floor(Math.random() * Date.now())} class=${readMoreClass}><div class=${readMoreClassText}>${selectedText}</div><button class="c-btn -normal -link cta_1 read-more-toggle js-read-more-toggle" data-show-less-text="Read less"><span class="c-btn__txt">Read More</span></button></div>`;
            if (selectedText.includes(readMoreClass)) {
                let removeRM = [];
                selectedText.replace(/id="(rm-\d+)"/g, (match, id) => {
                    removeRM.push(id)
                });
                if (removeRM.length > 0) {
                    removeRM.forEach(id => {
                        const readMore = iframeElement.contentWindow.document.getElementById(id);
                        const readMoreText = readMore.querySelector(`.${readMoreClassText}`);
                        const children = readMoreText.childNodes;
                        const fragment = document.createDocumentFragment();
                        Array.from(children).forEach((child) => fragment.appendChild(child));
                        readMore.replaceWith(fragment)
                    });
                }
            } else if (closestReadMore) {
                const readMoreText = closestReadMore.querySelector(`.${readMoreClassText}`);
                const children = readMoreText.childNodes;
                const fragment = document.createDocumentFragment();
                Array.from(children).forEach((child) => fragment.appendChild(child));
                closestReadMore.replaceWith(fragment)
            } else if (selectedText.length === 0) {
                alert('Please select the text for read more content');
                return;
            } else {
                editor.execCommand('mceReplaceContent', false, createReadMore);
            }
        });
    });
})();