import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class CardTokenization extends Plugin {
    static options = {
        posId: '',
        payuScriptUrl: '',
        fingerprintScriptUrl: 'https://openfpcdn.io/fingerprintjs/v4/iife.min.js',
        iframeContainerSelector: '#payu-card-iframe-container',
        paymentFormSelector: '#confirmOrderForm',
        formButton: '#confirmFormSubmit',
        multiUseToken: '#multiUseToken',
        tokenInputName: 'payuCardToken',
        maskedCardInputName: 'payuMaskedCard',
        cardHolderInputName: 'payuCardHolder',
        visitorIdInputName: 'visitorId',
        errorMessageSelector: '.payu-card-payment-error',
        loadingIndicatorSelector: '.payu-card-payment-loading',
        paymentMethodRadioSelector: '.payu-payment-method-radio',
        cardPaymentContainerSelector: '.payu-card-payment-container',
    };

    init() {
        this.iframeContainer = DomAccess.querySelector(this.el, this.options.iframeContainerSelector, false);
        this.errorMessageElement = DomAccess.querySelector(this.el, this.options.errorMessageSelector, false);
        this.loadingIndicator = DomAccess.querySelector(this.el, this.options.loadingIndicatorSelector, false);
        this.paymentForm = DomAccess.querySelector(document, this.options.paymentFormSelector, false);
        this.formButton = DomAccess.querySelector(document, this.options.formButton, false);
        this.paymentMethodRadios = DomAccess.querySelectorAll(document, this.options.paymentMethodRadioSelector, false);
        this.cardPaymentContainer = DomAccess.querySelector(document, this.options.cardPaymentContainerSelector, false);

        // If no radio buttons exist (embedded mode), default to tokenize
        // Otherwise default to redirect and let user choose
        this.paymentType = (this.paymentMethodRadios && this.paymentMethodRadios.length) ? 'redirect' : 'tokenize';

        this._handlePaymentType();
        this._initializeFingerprint();
        this._initializePayU();
        this._registerEvents();
    }

    _registerEvents()
    {
        if (!this.formButton || !this.paymentForm) return;

        const isTokenize = this.paymentType === 'tokenize';

        this.formButton.removeEventListener('click', this._handleTokenizeButtonClick);
        this.paymentForm.removeEventListener('submit', this._preventDefault);

        this._handleTokenizeButtonClick = (e) => {
            e.preventDefault();
            this.constructor.prototype._handleTokenizeButtonClick.call(this, e);
        };

        this._preventDefault = (e) => {
            if (!this._allowPaymentFormSubmit) {
                e.preventDefault();
            }
        };

        if (isTokenize) {
            this.formButton.addEventListener('click', this._handleTokenizeButtonClick);
            this.paymentForm.addEventListener('submit', this._preventDefault);
        }
    }

    _initializePayU(initialTokenType = 'SINGLE') {
        this.selectedTokenType = initialTokenType;

        this._loadScript(this.options.payuScriptUrl).then(() => {
           this.payuInstance = PayU(this.options.posId);

           // SecureForm styling options
           const secureFormOptions = {
               style: {
                   basic: {
                       fontSize: '16px',
                       fontWeight: '400',
                       fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                       fontColor: '#212529',
                   },
                   focus: {
                       fontColor: '#212529',
                   },
                   placeholder: {
                       color: '#6c757d',
                   },
                   invalid: {
                       fontColor: '#dc3545',
                   },
                   valid: {
                       fontColor: '#198754',
                   }
               },
               placeholder: {
                   number: 'Numer karty',
                   date: 'MM/RR',
                   cvv: 'CVV'
               },
               lang: 'pl'
           };

           const secureForm = this.payuInstance.secureForms().add('card', secureFormOptions);

           if (this.iframeContainer && !this.iframeContainer.children.length) {
               secureForm.render(this.options.iframeContainerSelector);
           }
        }).catch(error => {
            console.error('Error loading PayU SDK:', error);
            this._displayError('Failed to load payment SDK.');
        });
    }

    _handleTokenizeButtonClick(event) {
        event.preventDefault();

        // A saved card chosen via the shared bundle cr-saved-card-selector writes its
        // token straight into the payuCardToken hidden input; skip SecureForm tokenization.
        if (this._hasSavedCardToken()) {
            this._submitPaymentForm();
            return;
        }

        if (!this.payuInstance) {
            this._displayError('Payment SDK not ready. Please try again.');
            return;
        }

        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = 'block';
        }

        const multiUseCheckbox = DomAccess.querySelector(document, this.options.multiUseToken);
        const tokenType = multiUseCheckbox && multiUseCheckbox.checked ? 'MULTI' : 'SINGLE';

        this.payuInstance.tokenize(tokenType)
            .then((result) => {
                if (this.loadingIndicator) {
                    this.loadingIndicator.style.display = 'none';
                }

                if (result.status === 'SUCCESS' && result.body && result.body.token) {
                    this._createHiddenInput(this.options.tokenInputName, result.body.token);
                    if (result.body.maskedCard) {
                        this._createHiddenInput(this.options.maskedCardInputName, result.body.maskedCard);
                    }
                    if (result.body.cardHolderName) { 
                        this._createHiddenInput(this.options.cardHolderInputName, result.body.cardHolderName);
                    }

                    this._submitPaymentForm();

                } else {
                    let errorMessage = 'Tokenization failed. Please check your card details.';
                    if (result.error?.message) {
                        errorMessage = result.error.message;
                    }
                    this._displayError(errorMessage);
                }
            })
            .catch((error) => {
                if (this.loadingIndicator) {
                    this.loadingIndicator.style.display = 'none';
                }
                this._displayError('An unexpected error occurred during tokenization.');
            });
    }

    /**
     * Handle payment type radio buttons to choose if customer wants to
     * tokenize card or be redirected into PayU pay wall.
     * In embedded mode (no radio buttons), shows card form immediately.
     * @private
     */
    _handlePaymentType() {
        // Embedded mode - no radio buttons, show card form immediately
        if (!this.paymentMethodRadios || !this.paymentMethodRadios.length) {
            if (this.cardPaymentContainer) {
                this.cardPaymentContainer.style.display = 'block';
                this.cardPaymentContainer.style.opacity = '1';
            }
            return;
        }

        // Legacy mode with radio buttons for user choice
        this.paymentMethodRadios.forEach(radio => {
            radio.addEventListener('change', (event) => {
                const selectedValue = event.target.value;

                this.paymentType = selectedValue;
                this._registerEvents();

                if (selectedValue === 'tokenize') {
                    this.cardPaymentContainer.style.display = 'block';
                    void this.cardPaymentContainer.offsetHeight;
                    this.cardPaymentContainer.style.opacity = '1';
                } else {
                    this.cardPaymentContainer.style.opacity = '0';
                    setTimeout(() => {
                        this.cardPaymentContainer.style.display = 'none';
                    }, 300);
                }
            });
        });
    }

    /**
     * Load a script dynamically
     * @private
     * @param {string} url - Script URL to load
     * @returns {Promise}
     */
    _loadScript(url) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Init device fingerprint
     * @private
     */
    _initializeFingerprint() {
        this._loadScript(this.options.fingerprintScriptUrl)
            .then(() => {
                if (window.FingerprintJS) {
                    return window.FingerprintJS.load();
                }
                throw new Error('FingerprintJS not loaded');
            })
            .then(fp => fp.get())
            .then(result => {
                const visitorId = result.visitorId;
                this._createHiddenInput(this.options.visitorIdInputName, visitorId);
            })
            .catch(error => {
                console.error('Error loading Fingerprint SDK:', error);
            });
    }

    /**
     * Create hidden input in DOM
     * @param name
     * @param value
     * @private
     */
    _createHiddenInput(name, value) {
        if (!this.paymentForm) {
            console.error('Payment form not found. Cannot add hidden input.');
            return;
        }
        let input = this.paymentForm.querySelector(`input[name="${name}"]`);
        if (!input) {
            input = document.createElement('input');
            input.setAttribute('type', 'hidden');
            input.setAttribute('name', name);
            this.paymentForm.appendChild(input);
        }
        input.setAttribute('value', value);
    }

    /**
     * Display error
     * @param message
     * @private
     */
    _displayError(message) {
        if (this.errorMessageElement) {
            this.errorMessageElement.innerText = message;
            this.errorMessageElement.style.display = 'block';
        }
    }

    /**
     * Submit the checkout form through requestSubmit() so the native submit event
     * and constraint validation still run (the tokenize-mode _preventDefault
     * listener is bypassed via the _allowPaymentFormSubmit flag). Falls back to
     * submit() only when requestSubmit() is unavailable.
     * @private
     */
    _submitPaymentForm() {
        this._allowPaymentFormSubmit = true;

        if (typeof this.paymentForm.requestSubmit === 'function') {
            this.paymentForm.requestSubmit();
            window.setTimeout(() => {
                this._allowPaymentFormSubmit = false;
            }, 0);

            return;
        }

        if (typeof this.paymentForm.reportValidity === 'function' && !this.paymentForm.reportValidity()) {
            this._allowPaymentFormSubmit = false;
            return;
        }

        this.paymentForm.submit();
    }

    /**
     * Whether the shared bundle saved-card selector has populated the card token.
     * @private
     * @returns {boolean}
     */
    _hasSavedCardToken() {
        const input = this.paymentForm
            ? this.paymentForm.querySelector(`input[name="${this.options.tokenInputName}"]`)
            : null;

        return !!(input && input.value);
    }
}
