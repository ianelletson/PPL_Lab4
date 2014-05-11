stmt([pass|T], T) :- not(terminal(T)).
stmt([declare,X|More], More) :- not(terminal(X)).
stmt([use,X|More], More) :-  not(terminal(X)).
stmt(A) :- stmt(A, []).

%TODO: stmts
stmts(T,T).
stmts(A) :- stmts(A, []).

%Facts
terminal(pass).
terminal(declare).
terminal(use).
