<template>

    <div class="live-preview-contents min-h-screen">
        <iframe ref="iframe" frameborder="0" class="min-h-screen" />
    </div>

</template>

<script>
let source;

export default {

    data() {
        return {
            payload: null,
            channel: null
        }
    },

    created() {
        this.channel = new BroadcastChannel('livepreview');

        this.channel.onmessage = e => {
            switch (e.data.event) {
                case 'updated':
                    this.url = e.data.url;
                    this.payload = e.data.payload;
                    this.update();
                    break;
                case 'ping':
                    this.channel.postMessage({ event: 'popout.pong' });
                    break;
                default:
                    break;
            }
        };

        this.channel.postMessage({ event: 'popout.opened' });
    },

    methods: {

        update: _.debounce(function () {
            if (source) source.cancel();
            source = this.$axios.CancelToken.source();

            this.channel.postMessage({ event: 'popout.loading' });

            this.$axios.post(this.url, this.payload, { cancelToken: source.token }).then(response => {
                this.updateIframeContents(response.data);
            }).catch(e => {
                if (this.$axios.isCancel(e)) return;
                throw e;
            });
        }, 150),

        updateIframeContents(contents) {
            const iframe = this.$refs.iframe;
            const scrollX = $(iframe.contentWindow.document).scrollLeft();
            const scrollY = $(iframe.contentWindow.document).scrollTop();

            // the statamicResetPreview() patch is needed for our frontend scripts
            // until https://github.com/statamic/ideas/issues/382 is part of core
            contents +=
                '<script type="text/javascript">window.statamicResetPreview = function(){window.location="' +
                cp_url('livepreview/loading-screen') +
                '";};window.scrollTo(' +
                scrollX + 
                ', ' +
                scrollY +
                ');\x3c/script>';

            const handleIframeReady = () => {
                iframe.contentWindow.document.open();
                iframe.contentWindow.document.write(contents);
                iframe.contentWindow.document.close();
                this.channel.postMessage({ event: 'popout.loaded' });
            };

            if (
                iframe.contentWindow.statamicResetPreview
                && typeof iframe.contentWindow.statamicResetPreview === 'function'
            ) {
                iframe.contentWindow.statamicResetPreview();
                iframe.addEventListener('load', handleIframeReady, { once: true });
            } else {
                handleIframeReady();
            }
        },

    }

}
</script>
