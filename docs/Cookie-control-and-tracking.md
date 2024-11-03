# Civic Cookie control

GSTC had a license for https://www.civicuk.com/cookie-control/documentation. To prevent cookies from being dropping we need to include an external JS filed and instantiate an object with config options.

```html
<!-- Latest Version -->
<script src="https://cc.cdn.civiccomputing.com/9/cookieControl-9.x.min.js" type="text/javascript"></script>
<script>
var config = {
  apiKey: 'XXXXXXXXXXXXXXXXXXXXXXXX',
  product: 'PRO',
};

CookieControl.load( config );
</script>
```

## Integration with tag manager
On acceptance or when revoked DataLayer events are fired. A dataLayer variable `civic_cookies_analytics` can then be used in GTM to determine consent.

```html
var config = {
	...
	branding : {
		...
	},
	optionalCookies: [
		{
				name : 'analytics',
				label: 'Analytical Cookies',
				description: 'Analytical cookies help us to improve our website by collecting and reporting information on its usage.',
				cookies: ['_ga', '_gid', '_gat', '__utma', '__utmt', '__utmb', '__utmc', '__utmz', '__utmv'],
				onAccept : function(){
					dataLayer.push({
						'event': 'analytics_consent_given',
						'civic_cookies_analytics': 'consent_given'
						});
				},
				onRevoke : function(){
					dataLayer.push({
						'civic_cookies_analytics': 'consent_revoked'
						});
				}

				
		}
]
};
```

Currently, only analytics cookies are prevented. If more optional cookie groupings are added in the future then a similar approach should be taken. E.G if marketing grouping had been added below is an example of events.

```html
onAccept : function(){
        name : 'analytics',
        label: 'Marketing Cookies',
        description: 'Marketing cookies ....',
        cookies: ['XXX],
        dataLayer.push({
          'event': 'marketing_consent_given',
          'civic_cookies_marketing': 'consent_given'
          });
        },
        onRevoke : function(){
          dataLayer.push({
            'civic_cookies_marketing': 'consent_revoked'
            });
        }

```

### Tag manager exclusions

A trigger called `Cookie consent analytics - NOT GIVEN` has been created that fires whenever `civic_cookies_marketing` does not equal `consent_given`. When this exception triggers. It will prevent the tag it's attached to firing.

This allows control in tag manager on a per tag basis to manage consent.