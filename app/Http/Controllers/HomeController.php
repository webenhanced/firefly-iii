<?php namespace FireflyIII\Http\Controllers;

use Artisan;
use Carbon\Carbon;
use Config;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use Input;
use Preferences;
use Session;
use Steam;

/**
 * Class HomeController
 *
 * @package FireflyIII\Http\Controllers
 */
class HomeController extends Controller
{

    public function dateRange()
    {
        $start = new Carbon(Input::get('start'));
        $end   = new Carbon(Input::get('end'));

        $diff = $start->diffInDays($end);

        if ($diff > 50) {
            Session::flash('warning', $diff . ' days of data may take a while to load.');
        }

        Session::put('start', $start);
        Session::put('end', $end);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function flush()
    {

        Preferences::mark();

        // get all tags.
        // update all counts:
        $tags = Tag::get();

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            foreach ($tag->transactionjournals()->get() as $journal) {
                $count              = $journal->tags()->count();
                $journal->tag_count = $count;
                $journal->save();
            }
        }


        Session::clear();
        Artisan::call('cache:clear');

        return redirect(route('index'));
    }

    /**
     * @param ARI $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index(ARI $repository)
    {
        $types = Config::get('firefly.accountTypesByIdentifier.asset');
        $count = $repository->countAccounts($types);
        bcscale(2);

        if ($count == 0) {
            return redirect(route('new-user.index'));
        }

        $title             = 'Firefly';
        $subTitle          = trans('firefly.welcomeBack');
        $mainTitleIcon     = 'fa-fire';
        $transactions      = [];
        $frontPage         = Preferences::get('frontPageAccounts', []);
        $start             = Session::get('start', Carbon::now()->startOfMonth());
        $end               = Session::get('end', Carbon::now()->endOfMonth());
        $showTour          = Preferences::get('tour', true)->data;
        $accounts          = $repository->getFrontpageAccounts($frontPage);
        $savings           = $repository->getSavingsAccounts();
        $piggyBankAccounts = $repository->getPiggyBankAccounts();


        $savingsTotal = 0;
        foreach ($savings as $savingAccount) {
            $savingsTotal = bcadd($savingsTotal, Steam::balance($savingAccount, $end));
        }

        $sum = $repository->sumOfEverything();

        if ($sum != 0) {
            Session::flash(
                'error', 'Your transactions are unbalanced. This means a'
                         . ' withdrawal, deposit or transfer was not stored properly. '
                         . 'Please check your accounts and transactions for errors.'
            );
        }

        foreach ($accounts as $account) {
            $set = $repository->getFrontpageTransactions($account, $start, $end);

            if (count($set) > 0) {
                $transactions[] = [$set, $account];
            }
        }

        return view(
            'index', compact('count', 'showTour', 'title', 'savings', 'subTitle', 'mainTitleIcon', 'transactions', 'savingsTotal', 'piggyBankAccounts')
        );
    }

}
