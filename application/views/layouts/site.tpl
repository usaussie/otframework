{$this->doctype('XHTML1_STRICT')}
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>{$config.appTitle} - {$title}</title>
<link rel="stylesheet" type="text/css" media="all" href="{$sitePrefix}/public/ot/css/Ot/reset-fonts-grids.css" />
<link rel="stylesheet" type="text/css" media="all" href="{$sitePrefix}/public/ot/css/Ot/base.css" />
<link rel="stylesheet" type="text/css" media="all" href="{$sitePrefix}/public/ot/css/Ot/common.css" />
<link rel="stylesheet" type="text/css" media="all" href="{$sitePrefix}/public/css/layout.css" />
<link rel="stylesheet" type="text/css" media="all" href="{$sitePrefix}/public/css/nav.css" />
{foreach from=$css item=c}
<link rel="stylesheet" type="text/css" media="all" href="{$c}" />
{/foreach}
<link rel="stylesheet" type="text/css" media="all" href="{$sitePrefix}/public/css/overrides.css" />
<script type="text/javascript" src="{$sitePrefix}/public/ot/scripts/mootools.v1.11.js"></script>
<script type="text/javascript" src="{$sitePrefix}/public/ot/scripts/cnet/mootools.extended/Native/element.dimensions.js"></script>
<script type="text/javascript" src="{$sitePrefix}/public/ot/scripts/cnet/mootools.extended/Native/element.position.js"></script>
<script type="text/javascript" src="{$sitePrefix}/public/scripts/global.js"></script>
{if $useInlineEditor}
<script type="text/javascript" src="{$sitePrefix}/public/ot/scripts/moo.prompt.v1.js"></script>
<script type="text/javascript" src="{$sitePrefix}/public/ot/scripts/iEdit.js"></script>
{/if}
{if $useInlineEditor || $useTinyMce}
<script type="text/javascript" src="{$sitePrefix}/public/ot/scripts/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript" src="{$sitePrefix}/public/ot/scripts/tinyMceConfig.js"></script>
{/if}
{foreach from=$javascript item=script}
<script type="text/javascript" src="{$script}"></script>
{/foreach}
</head>
<body>
    <input type="hidden" name="sitePrefix" id="sitePrefix" value="{$sitePrefix}" />
    
    
    <div id="doc4" class="yui-t2"> 
        <div id="hd">
            <p class="authInfo">{layout section=auth}</p>
            <span id="appTitle">{$config.appTitle}</span>
        </div> 
        <div id="bd"> 
            <div id="yui-main"> 
                <div class="yui-b">
                    <div class="yui-g"> 
                        <div class="content">
                            {if count($messages) != 0}
							<div class="messageContainer">
							    <div class="message">
							    {foreach from=$messages item=m}
							    {$m}<br />
							    {/foreach}
							    </div>
							</div>
							{/if}
							
							<div id="pageTitle">{$title}</div>
							
                            {layout section=content}
                        </div> 
                    </div> 
                </div>
            </div> 
            <div class="yui-b">
                {layout section=nav}
            </div> 
        </div> 
        <div id="ft">
            {layout section=footer}
        </div> 
    </div> 
</body>
</html>