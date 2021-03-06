use LWP::UserAgent;
use HTTP::Request;
# use JSON::XS 'decode_json';

our $app_js_version = "1.2";
our $app_css_version = "1.1";
our $commission_engine_api_url = "https://office.stg1-opulenza.xyz:81";

our $rank_title = "Rank";
our $rank_title_plural = "Ranks";

our $affiliate = "Affiliate";
our $affiliate_plural = "Affiliates";

our $autoship = "Autoship";
our $autoship_plural = "Autoships";

my ($site, $password) = &getvalue('site,password','users','id', $uid);

$password =~ s/\\/\\\\/g;

my $ua = LWP::UserAgent->new;
my $request = HTTP::Request->new(POST => $commission_engine_api_url . '/api/auth/token');

$request->content_type('application/json');
$request->content('{"username":"'. $site .'","password":"'. $password .'"}');

my $response = $ua->request($request);

if ($response->is_success) {
    my $json_content = decode_json( $response->content );
    $content = $json_content->{'token'};
} else {
    my $content = "";
}
print <<EOS;

<input type="hidden" value="$content" id="commission-engine-access-token" name="commission-engine-access-token">

EOS

1;
