<?php namespace Cerbero\Auth\Http\Controllers;

use Cerbero\Auth\Commands\LogoutCommand;
use Cerbero\Auth\Http\Requests\LoginRequest;
use Cerbero\Auth\Http\Requests\RecoverRequest;
use Cerbero\Auth\Http\Requests\RegisterRequest;
use Cerbero\Auth\Http\Requests\ResetRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Routing\Controller;
use Cerbero\Auth\Invite;

class AuthController extends Controller {

	/**
	 * @author	Andrea Marco Sartori
	 * @var		Illuminate\Contracts\Bus\Dispatcher	$bus	Command bus dispatcher.
	 */
	protected $bus;
	
	/**
	 * Set the dependencies.
	 *
	 * @author	Andrea Marco Sartori
	 * @param	Illuminate\Contracts\Bus\Dispatcher	$bus
	 * @return	void
	 */
	public function __construct(Dispatcher $bus)
	{
		$this->bus = $bus;
	}

	/**
	 * Display the login page.
	 *
	 * @author	Andrea Marco Sartori
	 * @return	Illuminate\Http\Response
	 */
	public function showLogin()
	{
		$login = config('_auth.login.view');

		return view($login);
	}

	/**
	 * Log the user in.
	 *
	 * @author	Andrea Marco Sartori
	 * @return	Illuminate\Http\RedirectResponse
	 */
	public function login(LoginRequest $request)
	{
		$this->bus->dispatchFrom('Cerbero\Auth\Commands\LoginCommand', $request);

		return redirect()->route(config('_auth.login.redirect'));
	}

	/**
	 * Log the user out.
	 *
	 * @author	Andrea Marco Sartori
	 * @return	Illuminate\Http\RedirectResponse
	 */
	public function logout()
	{
		$this->bus->dispatchNow(new LogoutCommand);

		return redirect()->route(config('_auth.logout.redirect'));
	}

	/**
	 * Display the registration page.
	 *
	 * @author	Andrea Marco Sartori
	 * @return	Illuminate\Http\Response
	 */
	public function showRegister()
	{
		$register = config('_auth.register.view');

		return view($register);
	}

	/**
	 * Register the user.
	 *
	 * @author	Andrea Marco Sartori
	 * @return	Illuminate\Http\RedirectResponse
	 */
	public function register(RegisterRequest $request)
	{
		
		$invenable = config('_auth.register.invite');
		if ($invenable) {
			$invite = new Invite;

			$inv = $invite->getByCode($request['invite']);
			if (!$inv) {
				return redirect()->back()->withErrors('check code');
			}
		}
		
		
		$this->bus->pipeThrough([
			'Cerbero\Auth\Pipes\Register\Login',
			'Cerbero\Auth\Pipes\Register\Notify',
			'Cerbero\Auth\Pipes\Register\Hash',

		])->dispatchFrom('Cerbero\Auth\Commands\RegisterCommand', $request);

		return redirect()->route(config('_auth.register.redirect'))->withSuccess(trans('auth::register.success'));
	}

	/**
	 * Display the recover page.
	 *
	 * @author	Andrea Marco Sartori
	 * @return	Illuminate\Http\Response
	 */
	public function showRecover()
	{
		$recover = config('_auth.recover.view');

		return view($recover);
	}

	/**
	 * Remember the user password.
	 *
	 * @author	Andrea Marco Sartori
	 * @return	Illuminate\Http\RedirectResponse
	 */
	public function recover(RecoverRequest $request)
	{
		$this->bus->pipeThrough([
			'Cerbero\Auth\Pipes\Recover\Notify',
			'Cerbero\Auth\Pipes\Recover\Store',

		])->dispatchFrom('Cerbero\Auth\Commands\RecoverCommand', $request);

		return back()->withSuccess(trans('auth::recover.success'));
	}

	/**
	 * Display the reset page.
	 *
	 * @author	Andrea Marco Sartori
	 * @return	Illuminate\Http\Response
	 */
	public function showReset($token)
	{
		$reset = config('_auth.reset.view');

		return view($reset);
	}

	/**
	 * Reset the user password.
	 *
	 * @author	Andrea Marco Sartori
	 * @return	Illuminate\Http\RedirectResponse
	 */
	public function reset(ResetRequest $request, $token)
	{
		$this->bus->dispatchFrom('Cerbero\Auth\Commands\ResetCommand', $request, compact('token'));

		return redirect()->route('login.index')->withSuccess(trans('auth::reset.success'));
	}

}
