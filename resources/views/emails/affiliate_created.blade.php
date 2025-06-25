<html>
<body>
    <h1>Affiliate Created</h1>
    <p>Hello, a new affiliate has been created.</p>
    <p>Name: {{ $affiliate->user->name }}</p>
    <p>Email: {{ $affiliate->user->email }}</p>
    <p>Discount Code: {{ $affiliate->discount_code }}</p>
</body>
</html> 