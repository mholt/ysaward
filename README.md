ysaward
=======

An entire website for managing high-turnover YSA wards, with multi-stake support



Known existing sites
-----------------------

- https://ysa2.org



How to set up your own YSA ward website
-----------------------

Here's the thing... each site can host multiple stakes and wards. If you don't want
to go through the hassle of setting up your own copy of the site and hosting it yourself,
and if an existing site already has the features you need, try asking that site if your
ward or stake could just be added to theirs. This way, there's very little overhead.
It only takes about an hour or two (worst case) to set up a ward/stake on an existing site.

If you need some features that an existing copy of the site doesn't already have, you'll
have to contribute to the project. Then you can ask an existing site to implement your
new feature, or just make your own copy of the site and host it yourself.



How to contribute
-----------------------

Fork the project to build features or fixes into the site. Submit a pull request when
you think it's ready to be added into the main development tree. No guarantees it'll
happen, but that's okay.

Remember, updates to this repository do not automatically update any of the sites. That's
a pill, I know, that each upgrade would have to be done manually. I'm not sure of the best
way to handle this yet but I'm going to leave it to individual webmasters to figure it out.
A thorough changelog, especially of breaking changes, would be very helpful, with each
pull request or fork.



Site requirements/notes
-----------------------

#### Hosting

- Linux. Recommended Ubuntu Server 12 or newer.
- VPS or dedicated server. Shared hosting will not do because of various SMTP/HTTP requirements.
- Recommended: [Digital Ocean](http://www.digitalocean.com) or
	[Amazon Web Services EC2](http://aws.amazon.com/ec2/) or
	[RackSpace Cloud Servers](http://www.rackspace.com/cloud/servers/)
- PHP 5.3 or newer, with the [PECL_HTTP extension](http://php.net/manual/en/http.install.php) and
	[GD libraries](http://php.net/manual/en/book.image.php) installed
- Recommended web server: nginx. Used to use Apache, but it was slower and more difficult
- MySQL 5.2 or newer (I'm pretty sure, at least...)
- Ability to make outbound, external SMTP and HTTP connections
- Avoid CPanel-esque hosting (shared) at nearly any cost
- Domain name (recommended registrar: NameCheap) (about $11/year)
- Costs anywhere from $5-20 per month (if using a cloud instance)

#### Email/SMTP

- [Amazon SES](http://aws.amazon.com/ses/) account
- Production sending abilities enabled
- Verified domain name with the account
- IAM user/password
- DKIM properly configured
- Payment method added to the account
- Costs are minimal

#### Texting/SMS

- [Nexmo](http://www.nexmo.com) account -- tell them you're a non-profit, maybe they'll discount
- At least one sending number in the account (more if sending to multiple wards)
- Funds added to the account, preferably with auto-refill
- They're in the UK, so their pricing is primarily in euros

#### Address verification

- [SmartyStreets](http://smartystreets.com) account -- non-profits get a free subscription
- You just need an API key for your site's domain name (use the "HTML key")


#### Configuration/setup

- Completely fill out the [lib/defines_template.php](https://github.com/mholt/ysaward/blob/master/lib/defines_template.php)
file and rename it to `defines.php`.
- MySQL database schema is coming soon. You'll need it!
- For externally-provided services like email and texting, follow the vendor's instructions for
obtaining API keys and/or configuring your domain/site to work with their service.



Disclaimer/support
-----------------------

This was an entirely-donated project from the beginning. Yes, it's true that I maintain(ed) my stake's
implementation of the site because of a stake calling, but I provide absolutely no support beyond that.
In other words, setting up the site yourself or changing it is up to you and the community.



A brief history (with my lame excuses)
-----------------------

This project started as a weekend project at the request of my ward's executive secretary
who didn't want to keep fumbling over several spreadsheets with new member data and duplication
and errors happening all over the place.

It kind of grew from there when the stake wanted to use it (unofficially) and offer it to
the wards to ease their semester turnovers. I scrambled to put new features and functions
into place, so the code isn't necessarily best practice. However, it has some very nice
little gems in there related to error handling, failovers, UI, etc.

So no, there's no PHP framework here. No MVC. It's just plain-jane PHP mixed with HTML and
Javascript. Sorry if it's gross, but again, now it's open source: so feel free to improve
upon it. It does the job, seems to do it well, and I hope that others will find it useful.