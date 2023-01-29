<?php

const _RUN_MODE_ = "production";
const _API_TOKEN_ = "api_token";
const _UI_URL = "https://";
const _API_BASE_URL_ = (_RUN_MODE_ === "development" ? "http://localhost:3001" : "{app_domain}") . "/wordpress-caching";
const _AVAILABLE_POST_TYPES_ = ["post", "page"];

