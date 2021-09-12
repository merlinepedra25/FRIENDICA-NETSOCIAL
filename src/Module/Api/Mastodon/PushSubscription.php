<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Subscription;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/notifications/push/
 */
class PushSubscription extends BaseApi
{
	public static function post(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$request = self::getRequest([
			'subscription' => [],
			'data'         => [],
		]);

		$subscription = [
			'application-id' => $application['id'],
			'uid'            => $uid,
			'endpoint'       => $request['subscription']['endpoint'] ?? '',
			'pubkey'         => $request['subscription']['keys']['p256dh'] ?? '',
			'secret'         => $request['subscription']['keys']['auth'] ?? '',
			'follow'         => $request['data']['alerts']['follow'] ?? false,
			'favourite'      => $request['data']['alerts']['favourite'] ?? false,
			'reblog'         => $request['data']['alerts']['reblog'] ?? false,
			'mention'        => $request['data']['alerts']['mention'] ?? false,
			'poll'           => $request['data']['alerts']['poll'] ?? false,
			'follow_request' => $request['data']['alerts']['follow_request'] ?? false,
			'status'         => $request['data']['alerts']['status'] ?? false,
		];

		$ret = Subscription::replace($subscription);

		Logger::info('Subscription stored', ['ret' => $ret, 'subscription' => $subscription]);

		return DI::mstdnSubscription()->createForApplicationIdAndUserId($application['id'], $uid)->toArray();
	}

	public static function put(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$request = self::getRequest([
			'data' => [],
		]);

		$subscription = Subscription::select($application['id'], $uid, ['id']);
		if (empty($subscription)) {
			Logger::info('Subscription not found', ['application-id' => $application['id'], 'uid' => $uid]);
			DI::mstdnError()->RecordNotFound();
		}

		$fields = [
			'follow'         => $request['data']['alerts']['follow'] ?? false,
			'favourite'      => $request['data']['alerts']['favourite'] ?? false,
			'reblog'         => $request['data']['alerts']['reblog'] ?? false,
			'mention'        => $request['data']['alerts']['mention'] ?? false,
			'poll'           => $request['data']['alerts']['poll'] ?? false,
			'follow_request' => $request['data']['alerts']['follow_request'] ?? false,
			'status'         => $request['data']['alerts']['status'] ?? false,
		];

		$ret = Subscription::update($application['id'], $uid, $fields);

		Logger::info('Subscription updated', ['result' => $ret, 'application-id' => $application['id'], 'uid' => $uid, 'fields' => $fields]);

		return DI::mstdnSubscription()->createForApplicationIdAndUserId($application['id'], $uid)->toArray();
	}

	public static function delete(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$ret = Subscription::delete($application['id'], $uid);

		Logger::info('Subscription deleted', ['result' => $ret, 'application-id' => $application['id'], 'uid' => $uid]);

		System::jsonExit([]);
	}

	public static function rawContent(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		if (!Subscription::exists($application['id'], $uid)) {
			Logger::info('Subscription not found', ['application-id' => $application['id'], 'uid' => $uid]);
			DI::mstdnError()->RecordNotFound();
		}

		Logger::info('Fetch subscription', ['application-id' => $application['id'], 'uid' => $uid]);

		return DI::mstdnSubscription()->createForApplicationIdAndUserId($application['id'], $uid)->toArray();
	}
}