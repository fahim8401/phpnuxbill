{include file="sections/header.tpl"}

<form method="post" role="form" action="{$_url}paymentgateway/uddoktapay" id="site-form">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('UddoktaPay Configuration')}</div>
                <div class="panel-body">

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('API Key')}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="uddoktapay_api_key" name="uddoktapay_api_key" 
                                   placeholder="Your UddoktaPay API Key" value="{$config['uddoktapay_api_key']}">
                            <p class="help-block">Get your API Key from UddoktaPay dashboard</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('API URL')}</label>
                        <div class="col-md-6">
                            <input type="url" class="form-control" id="uddoktapay_api_url" name="uddoktapay_api_url" 
                                   placeholder="https://sandbox.uddoktapay.com" value="{$config['uddoktapay_api_url']}">
                            <p class="help-block">
                                Sandbox: https://sandbox.uddoktapay.com<br>
                                Live: https://uddoktapay.com
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Store ID')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="uddoktapay_store_id" name="uddoktapay_store_id" 
                                   placeholder="Your UddoktaPay Store ID" value="{$config['uddoktapay_store_id']}">
                            <p class="help-block">Get your Store ID from UddoktaPay dashboard</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Webhook Key')}</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="uddoktapay_webhook_key" name="uddoktapay_webhook_key" 
                                   placeholder="Your UddoktaPay Webhook Key (Optional)" value="{$config['uddoktapay_webhook_key']}">
                            <p class="help-block">Optional: For webhook signature verification</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Callback URL')}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" readonly value="{$_url}callback/uddoktapay">
                            <p class="help-block">Set this URL in your UddoktaPay webhook configuration</p>
                        </div>
                    </div>

                </div>
                <div class="panel-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa fa-save"></i> {Lang::T('Save')}
                            </button>
                            <br>
                        </div>
                        <div class="col-md-6">
                            <div class="bs-callout bs-callout-info" id="callout-navbar-role">
                                <h4>{Lang::T('Information')}</h4>
                                <p>{Lang::T('UddoktaPay is a popular payment gateway in Bangladesh.')}</p>
                                <p>
                                    {Lang::T('Get your API credentials from')}: 
                                    <a href="https://uddoktapay.com" target="_blank">UddoktaPay Dashboard</a>
                                </p>
                                <p><strong>{Lang::T('Supported Currencies')}: </strong>BDT (Bangladeshi Taka)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{include file="sections/footer.tpl"}