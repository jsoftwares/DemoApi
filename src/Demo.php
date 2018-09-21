<?php

namespace Demo;

const ListId = 123;

class Demo
{
    public function main()
    {
        GraphqApiClient::Initialize("https://samskipti.zenter.is/Api/V2");
		GraphqApiClient::Version();

		$token = GraphqApiClient::Login("<ApiUser>", "<ApiPassphrase>");

		if (!$token)
		{
			throw new Exception("Could not login");
		}

		GraphqApiClient::Initialize("https://samskipti.zenter.is/Api/V2?token={$token}");
		if (!GraphqApiClient::IsPriviliged())
		{
			throw new Exception("Login attempt failed");
		}

        GraphqApiClient::AddRecipientsToList(ListId, [
            123,
            456
        ]);
    }
}


(new Demo())->main();